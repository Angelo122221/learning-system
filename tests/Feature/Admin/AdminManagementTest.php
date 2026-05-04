<?php

namespace Tests\Feature\Admin;

use App\Models\Announcement;
use App\Models\CarouselImage;
use App\Models\FeaturedVideo;
use App\Models\Folder;
use App\Models\ResourceFile;
use App\Models\ResourceTracking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_users_and_analytics_pages(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/analytics')
            ->assertOk();
    }

    public function test_admin_can_create_update_and_delete_teacher_accounts(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Teacher One',
                'email' => 'teacher.one@deped.gov.ph',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'teacher',
                'district' => 'District A',
                'school_name' => 'School A',
            ])
            ->assertRedirect();

        $teacher = User::where('email', 'teacher.one@deped.gov.ph')->firstOrFail();

        $this->actingAs($admin)
            ->patch("/admin/users/{$teacher->id}", [
                'name' => 'Teacher Updated',
                'email' => 'teacher.updated@deped.gov.ph',
                'role' => 'teacher',
                'district' => 'District B',
                'school_name' => 'School B',
            ])
            ->assertRedirect();

        $teacher->refresh();
        $this->assertSame('Teacher Updated', $teacher->name);
        $this->assertSame('District B', $teacher->district);

        $this->actingAs($admin)
            ->patch("/admin/users/{$teacher->id}/password", [
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])
            ->assertRedirect();

        $teacher->refresh();
        $this->assertTrue(Hash::check('newpassword123', $teacher->password));

        $this->actingAs($admin)
            ->delete("/admin/users/{$teacher->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('users', [
            'id' => $teacher->id,
        ]);
    }

    public function test_admin_can_create_update_and_delete_carousel_entries(): void
    {
        Storage::fake('public');

        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->post('/admin/carousel', [
                'title' => 'Slide 1',
                'image' => UploadedFile::fake()->image('slide1.jpg'),
            ])
            ->assertRedirect();

        $carousel = CarouselImage::firstOrFail();

        $this->actingAs($admin)
            ->patch("/admin/carousel/{$carousel->id}", [
                'title' => 'Slide Updated',
            ])
            ->assertRedirect();

        $carousel->refresh();
        $this->assertSame('Slide Updated', $carousel->title);

        $this->actingAs($admin)
            ->delete("/admin/carousel/{$carousel->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('carousel_images', [
            'id' => $carousel->id,
        ]);
    }

    public function test_carousel_delete_from_inertia_uses_see_other_redirect(): void
    {
        Storage::fake('public');

        $admin = $this->createAdminUser();

        $imagePath = UploadedFile::fake()->image('slide-delete.jpg')->store('carousel', 'public');

        $carousel = CarouselImage::create([
            'title' => 'Delete slide',
            'image_path' => $imagePath,
        ]);

        $this->actingAs($admin)
            ->from('/admin/carousel')
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->delete("/admin/carousel/{$carousel->id}")
            ->assertStatus(303)
            ->assertRedirect('/admin/carousel');

        $this->assertDatabaseMissing('carousel_images', [
            'id' => $carousel->id,
        ]);
    }

    public function test_authenticated_user_can_access_public_disk_media_through_media_route(): void
    {
        Storage::fake('public');

        $admin = $this->createAdminUser();
        Storage::disk('public')->put('carousel/test-slide.jpg', 'fake-image-content');

        $this->actingAs($admin)
            ->get('/media/carousel/test-slide.jpg')
            ->assertOk();
    }

    public function test_admin_can_create_update_and_delete_featured_videos(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->post('/admin/videos', [
                'youtube_link' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'description' => 'First video',
            ])
            ->assertRedirect();

        $video = FeaturedVideo::firstOrFail();

        $this->actingAs($admin)
            ->patch("/admin/videos/{$video->id}", [
                'youtube_link' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
                'description' => 'Updated video',
            ])
            ->assertRedirect();

        $video->refresh();
        $this->assertSame('Updated video', $video->description);

        $this->actingAs($admin)
            ->delete("/admin/videos/{$video->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('featured_videos', [
            'id' => $video->id,
        ]);
    }

    public function test_folder_delete_from_inertia_uses_see_other_redirect(): void
    {
        $admin = $this->createAdminUser();

        $folder = Folder::create([
            'name' => 'Delete Folder',
            'parent_id' => null,
        ]);

        $this->actingAs($admin)
            ->from('/admin/resources')
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->delete("/admin/folders/{$folder->id}")
            ->assertStatus(303)
            ->assertRedirect('/admin/resources');

        $this->assertDatabaseMissing('folders', [
            'id' => $folder->id,
        ]);
    }

    public function test_file_delete_from_inertia_uses_see_other_redirect(): void
    {
        Storage::fake('public');

        $admin = $this->createAdminUser();

        $folder = Folder::create([
            'name' => 'Delete File Folder',
            'parent_id' => null,
        ]);

        $filePath = UploadedFile::fake()->create('teacher-guide.pdf', 32, 'application/pdf')
            ->store('resources', 'public');
        $previewPath = UploadedFile::fake()->image('teacher-guide-preview.jpg')
            ->store('resources/previews', 'public');

        $file = ResourceFile::create([
            'folder_id' => $folder->id,
            'title' => 'Teacher Guide',
            'category' => 'Modules',
            'file_path' => $filePath,
            'preview_image_path' => $previewPath,
            'file_type' => 'pdf',
            'is_locked' => false,
        ]);

        $this->actingAs($admin)
            ->from('/admin/resources')
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->delete("/admin/files/{$file->id}")
            ->assertStatus(303)
            ->assertRedirect('/admin/resources');

        $this->assertDatabaseMissing('resource_files', [
            'id' => $file->id,
        ]);
        $this->assertFalse(Storage::disk('public')->exists($filePath));
        $this->assertFalse(Storage::disk('public')->exists($previewPath));
    }

    public function test_delete_request_to_admin_resources_redirects_to_get_resources(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->delete('/admin/resources')
            ->assertStatus(303)
            ->assertRedirect('/admin/resources');
    }

    public function test_user_edit_from_inertia_uses_see_other_redirect(): void
    {
        $admin = $this->createAdminUser();
        $teacher = $this->createTeacherUser([
            'name' => 'Edit Target',
            'email' => 'edit.target@deped.gov.ph',
            'district' => 'District A',
            'school_name' => 'School A',
        ]);

        $this->actingAs($admin)
            ->from('/admin/users')
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->patch("/admin/users/{$teacher->id}", [
                'name' => 'Edited Teacher',
                'email' => 'edited.teacher@deped.gov.ph',
                'role' => 'teacher',
                'district' => 'District B',
                'school_name' => 'School B',
            ])
            ->assertStatus(303)
            ->assertRedirect('/admin/users');

        $teacher->refresh();

        $this->assertSame('Edited Teacher', $teacher->name);
        $this->assertSame('edited.teacher@deped.gov.ph', $teacher->email);
        $this->assertSame('District B', $teacher->district);
        $this->assertSame('School B', $teacher->school_name);
    }

    public function test_user_password_update_from_inertia_uses_see_other_redirect(): void
    {
        $admin = $this->createAdminUser();
        $teacher = $this->createTeacherUser([
            'email' => 'password.update@deped.gov.ph',
        ]);

        $this->actingAs($admin)
            ->from('/admin/users')
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->patch("/admin/users/{$teacher->id}/password", [
                'password' => 'updatedpassword123',
                'password_confirmation' => 'updatedpassword123',
            ])
            ->assertStatus(303)
            ->assertRedirect('/admin/users');

        $teacher->refresh();
        $this->assertTrue(Hash::check('updatedpassword123', $teacher->password));
    }

    public function test_user_delete_from_inertia_uses_see_other_redirect(): void
    {
        $admin = $this->createAdminUser();
        $teacher = $this->createTeacherUser([
            'email' => 'delete.target@deped.gov.ph',
        ]);

        $this->actingAs($admin)
            ->from('/admin/users')
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->delete("/admin/users/{$teacher->id}")
            ->assertStatus(303)
            ->assertRedirect('/admin/users');

        $this->assertDatabaseMissing('users', [
            'id' => $teacher->id,
        ]);
    }

    public function test_admin_can_create_update_and_delete_announcements(): void
    {
        Storage::fake('public');

        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->post('/admin/announcements', [
                'title' => 'Portal Notice',
                'content' => 'Initial announcement content.',
                'image' => UploadedFile::fake()->image('announcement.jpg'),
            ])
            ->assertRedirect();

        $announcement = Announcement::firstOrFail();

        $this->assertSame('Portal Notice', $announcement->title);
        $this->assertNotNull($announcement->image_path);
        $this->assertTrue(Storage::disk('public')->exists($announcement->image_path));

        $this->actingAs($admin)
            ->patch("/admin/announcements/{$announcement->id}", [
                'title' => 'Portal Notice Updated',
                'content' => 'Updated announcement content.',
                'remove_image' => true,
            ])
            ->assertRedirect();

        $announcement->refresh();
        $this->assertSame('Portal Notice Updated', $announcement->title);
        $this->assertNull($announcement->image_path);

        $this->actingAs($admin)
            ->delete("/admin/announcements/{$announcement->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('announcements', [
            'id' => $announcement->id,
        ]);
    }

    public function test_announcement_delete_from_inertia_uses_see_other_redirect(): void
    {
        $admin = $this->createAdminUser();

        $announcement = Announcement::create([
            'title' => 'Delete me',
            'content' => 'This announcement should be deleted.',
        ]);

        $this->actingAs($admin)
            ->from('/admin/announcements')
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->delete("/admin/announcements/{$announcement->id}")
            ->assertStatus(303)
            ->assertRedirect('/admin/announcements');

        $this->assertDatabaseMissing('announcements', [
            'id' => $announcement->id,
        ]);
    }

    public function test_announcement_delete_accepts_method_spoofed_post_requests(): void
    {
        $admin = $this->createAdminUser();

        $announcement = Announcement::create([
            'title' => 'Delete me with spoofing',
            'content' => 'This announcement should be deleted through a POST request.',
        ]);

        $this->actingAs($admin)
            ->from('/admin/announcements')
            ->post("/admin/announcements/{$announcement->id}", [
                '_method' => 'delete',
            ])
            ->assertStatus(303)
            ->assertRedirect('/admin/announcements');

        $this->assertDatabaseMissing('announcements', [
            'id' => $announcement->id,
        ]);
    }

    public function test_admin_can_upload_a_single_resource_file(): void
    {
        Storage::fake('public');

        $admin = $this->createAdminUser();

        $folder = Folder::create([
            'name' => 'Science',
            'parent_id' => null,
        ]);

        $this->actingAs($admin)
            ->post('/admin/files', [
                'title' => 'Lesson Plan',
                'category' => 'Modules',
                'folder_id' => $folder->id,
                'file' => UploadedFile::fake()->create('lesson-plan.pdf', 120, 'application/pdf'),
                'preview_image' => UploadedFile::fake()->image('lesson-preview.jpg'),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $resourceFile = ResourceFile::firstOrFail();

        $this->assertSame('Lesson Plan', $resourceFile->title);
        $this->assertSame('Modules', $resourceFile->category);
        $this->assertSame('pdf', $resourceFile->file_type);
        $this->assertNotNull($resourceFile->preview_image_path);

        $this->assertTrue(Storage::disk('public')->exists($resourceFile->file_path));
        $this->assertTrue(Storage::disk('public')->exists($resourceFile->preview_image_path));
    }

    public function test_admin_cannot_upload_multiple_resource_files_in_a_single_request(): void
    {
        Storage::fake('public');

        $admin = $this->createAdminUser();

        $folder = Folder::create([
            'name' => 'Math',
            'parent_id' => null,
        ]);

        $this->actingAs($admin)
            ->from('/admin/resources')
            ->post('/admin/files', [
                'title' => 'Batch Upload',
                'category' => 'Test Questionnaires',
                'folder_id' => $folder->id,
                'file' => [
                    UploadedFile::fake()->create('one.pdf', 50, 'application/pdf'),
                    UploadedFile::fake()->create('two.pdf', 50, 'application/pdf'),
                ],
            ])
            ->assertRedirect('/admin/resources')
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('resource_files', 0);
    }

    public function test_admin_can_upload_a_video_file(): void
    {
        Storage::fake('public');

        $admin = $this->createAdminUser();

        $folder = Folder::create([
            'name' => 'Videos',
            'parent_id' => null,
        ]);

        $this->actingAs($admin)
            ->post('/admin/files', [
                'title' => 'Training Video',
                'category' => 'Learning Videos',
                'folder_id' => $folder->id,
                'file' => UploadedFile::fake()->create('training-video.mp4', 1024, 'video/mp4'),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $resourceFile = ResourceFile::firstOrFail();

        $this->assertSame('Training Video', $resourceFile->title);
        $this->assertSame('Learning Videos', $resourceFile->category);
        $this->assertSame('mp4', $resourceFile->file_type);
        $this->assertTrue(Storage::disk('public')->exists($resourceFile->file_path));
    }

    public function test_folder_open_tracking_endpoint_records_activity(): void
    {
        $teacher = $this->createTeacherUser([
            'role' => 'teacher',
            'district' => 'District C',
            'school_name' => 'School C',
        ]);

        $folder = Folder::create([
            'name' => 'Science',
            'parent_id' => null,
        ]);

        ResourceFile::create([
            'folder_id' => $folder->id,
            'title' => 'Lesson 1',
            'category' => 'Ebook',
            'file_path' => 'resources/lesson1.pdf',
            'file_type' => 'pdf',
            'is_locked' => false,
        ]);

        $this->actingAs($teacher)
            ->post("/resources/folders/{$folder->id}/open")
            ->assertNoContent();

        $this->assertDatabaseHas('resource_trackings', [
            'user_id' => $teacher->id,
            'folder_id' => $folder->id,
            'action' => 'folder_opened',
        ]);
    }

    public function test_analytics_page_displays_tracked_district_and_school_data(): void
    {
        $admin = $this->createAdminUser();

        $teacher = $this->createTeacherUser([
            'role' => 'teacher',
            'district' => 'North District',
            'school_name' => 'North National High School',
        ]);

        $folder = Folder::create([
            'name' => 'Math',
            'parent_id' => null,
        ]);

        $file = ResourceFile::create([
            'folder_id' => $folder->id,
            'title' => 'Algebra Basics',
            'category' => 'Ebook',
            'file_path' => 'resources/algebra.pdf',
            'file_type' => 'pdf',
            'is_locked' => false,
        ]);

        ResourceTracking::create([
            'user_id' => $teacher->id,
            'folder_id' => $folder->id,
            'resource_file_id' => null,
            'action' => 'folder_opened',
        ]);

        ResourceTracking::create([
            'user_id' => $teacher->id,
            'folder_id' => $folder->id,
            'resource_file_id' => $file->id,
            'action' => 'file_opened',
        ]);

        ResourceTracking::create([
            'user_id' => $teacher->id,
            'folder_id' => $folder->id,
            'resource_file_id' => $file->id,
            'action' => 'file_downloaded',
        ]);

        $this->actingAs($admin)
            ->get('/admin/analytics')
            ->assertOk()
            ->assertSee('North District')
            ->assertSee('North National High School')
            ->assertSee('Algebra Basics');
    }

    private function createAdminUser(array $overrides = []): User
    {
        /** @var User $admin */
        $admin = User::factory()->createOne(array_merge([
            'is_admin' => true,
            'role' => 'admin',
        ], $overrides));

        return $admin;
    }

    private function createTeacherUser(array $overrides = []): User
    {
        /** @var User $teacher */
        $teacher = User::factory()->createOne(array_merge([
            'role' => 'teacher',
        ], $overrides));

        return $teacher;
    }
}
