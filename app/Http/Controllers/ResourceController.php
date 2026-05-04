<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementUserState;
use App\Models\CarouselImage;
use App\Models\FeaturedVideo;
use App\Models\Folder;
use App\Models\ResourceFile;
use App\Models\ResourceTracking;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResourceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $announcementStates = $user
            ? $user->announcementStates()->get()->keyBy('announcement_id')
            : collect();

        return Inertia::render('User/Resources/Index', [
            'folders' => Folder::whereNull('parent_id')
                ->with(['files', 'childrenRecursive'])
                ->orderBy('name', 'asc')
                ->get(),
            'announcements' => $this->buildAnnouncementPayload($announcementStates),
            'carouselImages' => CarouselImage::latest()->get(),
            'featuredVideos' => FeaturedVideo::latest()->get(),
        ]);
    }

    public function dataPrivacy(Request $request): Response
    {
        $user = $request->user();
        $announcementStates = $user
            ? $user->announcementStates()->get()->keyBy('announcement_id')
            : collect();

        return Inertia::render('User/About/DataPrivacy', [
            'announcements' => $this->buildAnnouncementPayload($announcementStates),
        ]);
    }

    public function citizensCharter(): Response
    {
        return Inertia::render('User/About/CitizensCharter');
    }

    public function markAnnouncementRead(Request $request, Announcement $announcement): HttpResponse
    {
        $this->upsertAnnouncementState(
            $request->user()->id,
            $announcement->id,
            ['read_at' => now()],
        );

        return response()->noContent();
    }

    public function markAllAnnouncementsRead(Request $request): HttpResponse
    {
        $user = $request->user();
        $now = now();

        $existingStates = $user->announcementStates()
            ->get()
            ->keyBy('announcement_id');

        $visibleAnnouncementIds = Announcement::query()
            ->latest()
            ->get()
            ->reject(fn (Announcement $announcement) => $existingStates->get($announcement->id)?->dismissed_at !== null)
            ->pluck('id');

        if ($visibleAnnouncementIds->isEmpty()) {
            return response()->noContent();
        }

        foreach ($visibleAnnouncementIds as $announcementId) {
            $this->upsertAnnouncementState($user->id, $announcementId, ['read_at' => $now]);
        }

        return response()->noContent();
    }

    public function dismissAnnouncement(Request $request, Announcement $announcement): HttpResponse
    {
        $this->upsertAnnouncementState(
            $request->user()->id,
            $announcement->id,
            ['dismissed_at' => now()],
        );

        return response()->noContent();
    }

    public function openFolder(Folder $folder): HttpResponse
    {
        if ($folder->isCurrentlyLocked()) {
            abort(403, 'This folder is currently locked.');
        }

        $this->trackAction('folder_opened', $folder, null);

        return response()->noContent();
    }

    public function download(ResourceFile $file): BinaryFileResponse
    {
        if ($file->folder?->isCurrentlyLocked()) {
            abort(403, 'The parent folder is currently locked.');
        }

        if ($file->isCurrentlyLocked()) {
            abort(403, 'This file is currently locked and cannot be downloaded.');
        }

        $this->trackAction('file_downloaded', $file->folder, $file);

        return response()->download(
            Storage::disk('public')->path($file->file_path),
            $file->title
        );
    }

    public function preview(ResourceFile $file): BinaryFileResponse
    {
        if ($file->folder?->isCurrentlyLocked()) {
            abort(403, 'The parent folder is currently locked.');
        }

        if ($file->isCurrentlyLocked()) {
            abort(403, 'This file is currently locked and cannot be previewed.');
        }

        $this->trackAction('file_opened', $file->folder, $file);

        return response()->file(Storage::disk('public')->path($file->file_path));
    }

    public function media(string $path): BinaryFileResponse
    {
        $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');

        abort_if(
            $normalizedPath === ''
            || str_contains($normalizedPath, '../')
            || str_contains($normalizedPath, '..\\'),
            404
        );

        abort_unless(Storage::disk('public')->exists($normalizedPath), 404);

        return response()->file(Storage::disk('public')->path($normalizedPath));
    }

    private function trackAction(string $action, ?Folder $folder, ?ResourceFile $file): void
    {
        if (! Auth::check()) {
            return;
        }

        ResourceTracking::create([
            'user_id' => Auth::id(),
            'folder_id' => $folder?->id,
            'resource_file_id' => $file?->id,
            'action' => $action,
        ]);
    }

    private function buildAnnouncementPayload(Collection $announcementStates): Collection
    {
        return Announcement::query()
            ->latest()
            ->get()
            ->reject(function (Announcement $announcement) use ($announcementStates) {
                return $announcementStates->get($announcement->id)?->dismissed_at !== null;
            })
            ->map(function (Announcement $announcement) use ($announcementStates) {
                $state = $announcementStates->get($announcement->id);

                return [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'content' => $announcement->content,
                    'image_path' => $announcement->image_path,
                    'created_at' => $announcement->created_at?->toIso8601String(),
                    'read_at' => $state?->read_at?->toIso8601String(),
                    'is_read' => $state?->read_at !== null,
                ];
            })
            ->values();
    }

    private function upsertAnnouncementState(int $userId, int $announcementId, array $attributes): AnnouncementUserState
    {
        return AnnouncementUserState::updateOrCreate(
            [
                'user_id' => $userId,
                'announcement_id' => $announcementId,
            ],
            $attributes,
        );
    }
}
