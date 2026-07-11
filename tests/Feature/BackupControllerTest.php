<?php

namespace Tests\Feature;

use Tests\TestCase;

class BackupControllerTest extends TestCase
{
    public function test_it_returns_success_when_backup_command_succeeds(): void
    {
        $response = $this->postJson('/api/backup/run');

        $response->assertStatus(200)
            ->assertJsonPath('message', 'تم إنشاء نسخة احتياطية لقاعدة البيانات بنجاح.');
    }
}
