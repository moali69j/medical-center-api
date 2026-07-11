<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class BackupController extends Controller
{
    /**
     * تشغيل أمر النسخ الاحتياطي يدوياً.
     */
    public function run()
    {
        try {
            $basePath = base_path();
            $command = 'cd "' . $basePath . '" && php artisan backup:run --only-db';
            $output = [];
            $exitCode = 0;

            exec($command, $output, $exitCode);

            $outputText = trim(implode(PHP_EOL, $output));

            if ($exitCode !== 0) {
                $errorMessage = $outputText ?: 'Backup command exited with code ' . $exitCode;
                Log::error('Backup failed: ' . $errorMessage);

                return response()->json([
                    'message' => 'فشل إنشاء النسخة الاحتياطية.',
                    'error' => $errorMessage,
                ], 500);
            }

            return response()->json([
                'message' => 'تم إنشاء نسخة احتياطية لقاعدة البيانات بنجاح.',
                'output' => $outputText,
            ]);
        } catch (\Exception $e) {
            Log::error('Backup failed: ' . $e->getMessage());
            return response()->json(['message' => 'حدث خطأ أثناء إنشاء النسخة الاحتياطية.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * استعادة قاعدة البيانات من ملف مضغوط (ZIP) مرفوع.
     */
    public function restore(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:zip|max:50000', // max 50MB
        ]);

        $file = $request->file('backup_file');
        $tempDir = storage_path('app/temp/restore_' . time());

        try {
            // 1. إنشاء مجلد مؤقت
            if (!File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            // 2. استخراج الملف المضغوط (ZIP)
            $zip = new ZipArchive;
            if ($zip->open($file->getRealPath()) === true) {
                $zip->extractTo($tempDir);
                $zip->close();
            } else {
                throw new \Exception('فشل في فتح الملف المضغوط (ZIP).');
            }

            // 3. البحث عن ملف الـ SQL المضغوط (GZ) داخل المجلدات المستخرجة
            $gzFiles = File::allFiles($tempDir);
            $sqlGzFile = collect($gzFiles)->first(function ($file) {
                return str_ends_with($file->getFilename(), '.sql.gz');
            });

            if (!$sqlGzFile) {
                // قد يكون الملف .sql مباشرة
                $sqlFile = collect($gzFiles)->first(function ($file) {
                    return str_ends_with($file->getFilename(), '.sql');
                });
                if (!$sqlFile) {
                    throw new \Exception('لم يتم العثور على ملف قاعدة البيانات (.sql أو .sql.gz) داخل المرفق.');
                }
                $sqlPath = $sqlFile->getRealPath();
            } else {
                // 4. فك ضغط الـ GZ للحصول على الـ SQL
                $sqlPath = $tempDir . '/database_restore.sql';
                $bufferSize = 4096;
                $gz = gzopen($sqlGzFile->getRealPath(), 'rb');
                $out = fopen($sqlPath, 'wb');
                
                if ($gz === false || $out === false) {
                    throw new \Exception('فشل في قراءة أو فك ضغط ملف البيانات (.gz).');
                }

                while (!gzeof($gz)) {
                    fwrite($out, gzread($gz, $bufferSize));
                }
                
                fclose($out);
                gzclose($gz);
            }

            // 5. استيراد قاعدة البيانات وتشغيل الاستعلامات
            $sqlContent = file_get_contents($sqlPath);
            if (empty($sqlContent)) {
                throw new \Exception('ملف البيانات فارغ.');
            }

            // إيقاف الفحوصات الأجنبية مؤقتاً لتجنب الأخطاء أثناء الاستعادة
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::unprepared($sqlContent);
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // 6. تنظيف المجلد المؤقت
            File::deleteDirectory($tempDir);

            return response()->json(['message' => 'تم استعادة قاعدة البيانات بنجاح!']);
        } catch (\Exception $e) {
            // تنظيف المجلد المؤقت في حال حدوث خطأ
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
            Log::error('Restore failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'فشلت عملية الاستعادة: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
