<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\CarouselImage;
use App\Models\FeaturedVideo;
use App\Models\Folder;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialInventory;
use App\Models\ResourceFile;
use App\Models\ResourceTracking;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ResourceController extends Controller
{
    private const MAX_FILE_KB = 102400; // 100 MB

    private const MAX_PREVIEW_IMAGE_KB = 5120;

    private const RESOURCE_CATEGORIES = [
        'Ebook',
        'Test Questionnaires',
        'Modules',
        'Learning Videos',
        'Story Telling Videos',
        'Story Books',
    ];

    private const TRACKING_FOLDER_OPEN_ACTIONS = [
        'folder_opened',
        'opened_folder',
    ];

    private const TRACKING_FILE_OPEN_ACTIONS = [
        'file_opened',
        'viewed_file',
    ];

    private const TRACKING_FILE_DOWNLOAD_ACTIONS = [
        'file_downloaded',
        'downloaded_file',
    ];

    public function index(): Response
    {
        return Inertia::render('Admin/Resources/Index', [
            'folders' => Folder::whereNull('parent_id')
                ->with(['files', 'childrenRecursive'])
                ->orderBy('name', 'asc')
                ->get(),
            'allFolders' => $this->allFoldersWithPath(),
            'stats' => [
                'total_folders' => Folder::count(),
                'total_files' => ResourceFile::count(),
                'total_users' => User::count(),
                'total_teachers' => User::where('role', 'teacher')->count(),
            ],
            'uploadLimits' => $this->uploadLimits(),
            'resourceCategories' => self::RESOURCE_CATEGORIES,
        ]);
    }

    public function announcements(): Response
    {
        return Inertia::render('Admin/Resources/Announcements', [
            'announcements' => Announcement::latest()->get(),
        ]);
    }

    public function carousel(): Response
    {
        return Inertia::render('Admin/Resources/Carousel', [
            'carouselImages' => CarouselImage::latest()->get(),
        ]);
    }

    public function videos(): Response
    {
        return Inertia::render('Admin/Resources/Videos', [
            'featuredVideos' => FeaturedVideo::latest()->get(),
        ]);
    }

    public function users(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::query()
                ->select([
                    'id',
                    'name',
                    'email',
                    'is_admin',
                    'role',
                    'district',
                    'school_name',
                    'email_verified_at',
                    'created_at',
                ])
                ->orderByDesc('is_admin')
                ->orderBy('name')
                ->get(),
            'stats' => [
                'total_users' => User::count(),
                'total_admins' => User::where('is_admin', true)->count(),
                'total_teachers' => User::where('role', 'teacher')->count(),
            ],
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => [...$this->depedEmailRules(), Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::in(['teacher', 'user', 'admin'])],
            'district' => ['nullable', 'string', 'max:255', Rule::requiredIf($request->input('role') === 'teacher')],
            'school_name' => ['nullable', 'string', 'max:255', Rule::requiredIf($request->input('role') === 'teacher')],
        ]);

        if ($validator->fails()) {
            return to_route('admin.users', [], 303)
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => $validated['password'],
            'role' => $validated['role'],
            'is_admin' => $validated['role'] === 'admin',
            'district' => $validated['district'] ?: null,
            'school_name' => $validated['school_name'] ?: null,
            'email_verified_at' => now(),
        ]);

        return to_route('admin.users', [], 303)
            ->with('success', 'User account created successfully.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => [...$this->depedEmailRules(), Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['teacher', 'user', 'admin'])],
            'district' => ['nullable', 'string', 'max:255', Rule::requiredIf($request->input('role') === 'teacher')],
            'school_name' => ['nullable', 'string', 'max:255', Rule::requiredIf($request->input('role') === 'teacher')],
        ]);

        if ($validator->fails()) {
            return to_route('admin.users', [], 303)
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $isAdmin = $validated['role'] === 'admin';

        // Keep at least one admin account in the system.
        if ($user->is_admin && ! $isAdmin && User::where('is_admin', true)->count() <= 1) {
            return to_route('admin.users', [], 303)->withErrors([
                'role' => 'At least one admin account is required.',
            ]);
        }

        $user->update([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'role' => $validated['role'],
            'is_admin' => $isAdmin,
            'district' => $validated['district'] ?: null,
            'school_name' => $validated['school_name'] ?: null,
        ]);

        return to_route('admin.users', [], 303)
            ->with('success', 'User profile updated successfully.');
    }

    public function updateUserPassword(Request $request, User $user): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if ($validator->fails()) {
            return to_route('admin.users', [], 303)
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $user->update([
            'password' => $validated['password'],
        ]);

        return to_route('admin.users', [], 303)
            ->with('success', 'User password updated successfully.');
    }

    public function destroyUser(User $user): RedirectResponse
    {
        if (Auth::id() === $user->id) {
            return to_route('admin.users', [], 303)->withErrors([
                'user' => 'You cannot delete your own account while logged in.',
            ]);
        }

        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            return to_route('admin.users', [], 303)->withErrors([
                'user' => 'At least one admin account is required.',
            ]);
        }

        $user->delete();

        return to_route('admin.users', [], 303)
            ->with('success', 'User deleted successfully.');
    }

    public function storeFolder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:folders,id',
            'description' => 'nullable|string|max:2000',
        ]);

        Folder::create($validated);

        return back()->with('success', 'Folder created successfully.');
    }

    public function storeFile(Request $request): RedirectResponse
    {
        if (is_array($request->file('file'))) {
            return back()->withErrors([
                'file' => 'Please upload only one file at a time.',
            ])->withInput();
        }

        if (is_array($request->file('preview_image'))) {
            return back()->withErrors([
                'preview_image' => 'Please upload only one preview image at a time.',
            ])->withInput();
        }

        $category = (string) $request->input('category', '');
        $isVideoCategory = $this->isVideoResourceCategory($category);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => ['required', 'string', Rule::in(self::RESOURCE_CATEGORIES)],
            'file' => $isVideoCategory
                ? 'required|file'
                : 'required|file|max:'.self::MAX_FILE_KB,
            'folder_id' => 'required|exists:folders,id',
            'preview_image' => 'nullable|image|max:'.self::MAX_PREVIEW_IMAGE_KB,
        ]);

        $uploadedFile = $request->file('file');
        $path = $uploadedFile->store('resources', 'public');
        $extension = $uploadedFile->getClientOriginalExtension();

        $previewImagePath = null;
        if ($request->hasFile('preview_image')) {
            $previewImagePath = $request->file('preview_image')->store('resources/previews', 'public');
        }

        ResourceFile::create([
            'folder_id' => $validated['folder_id'],
            'title' => $validated['title'],
            'category' => $validated['category'],
            'file_path' => $path,
            'preview_image_path' => $previewImagePath,
            'file_type' => $extension,
            'is_locked' => false,
        ]);

        return back()->with('success', 'File uploaded successfully.');
    }

    public function destroyFolder(Folder $folder): RedirectResponse
    {
        $folder->delete();

        return to_route('admin.resources', [], 303)
            ->with('success', 'Folder deleted successfully.');
    }

    public function destroyFile(ResourceFile $file): RedirectResponse
    {
        if ($file->file_path) {
            Storage::disk('public')->delete($file->file_path);
        }

        if ($file->preview_image_path) {
            Storage::disk('public')->delete($file->preview_image_path);
        }

        $file->delete();

        return to_route('admin.resources', [], 303)
            ->with('success', 'File deleted successfully.');
    }

    public function toggleFolderLock(Folder $folder): RedirectResponse
    {
        $unlocking = $folder->is_locked;

        $folder->update([
            'is_locked' => ! $folder->is_locked,
            'unlock_starts_at' => $unlocking ? null : $folder->unlock_starts_at,
            'unlock_ends_at' => $unlocking ? null : $folder->unlock_ends_at,
        ]);

        return back(303);
    }

    public function toggleFileLock(ResourceFile $file): RedirectResponse
    {
        $unlocking = $file->is_locked;

        $file->update([
            'is_locked' => ! $file->is_locked,
            'unlock_starts_at' => $unlocking ? null : $file->unlock_starts_at,
            'unlock_ends_at' => $unlocking ? null : $file->unlock_ends_at,
        ]);

        return back(303);
    }

    public function setFolderUnlockWindow(Request $request, Folder $folder): RedirectResponse
    {
        $message = $this->applyUnlockWindow($request, $folder, 'folder');

        return back(303)->with('success', $message);
    }

    public function setFileUnlockWindow(Request $request, ResourceFile $file): RedirectResponse
    {
        $message = $this->applyUnlockWindow($request, $file, 'file');

        return back(303)->with('success', $message);
    }

    public function storeCarousel(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'image' => ['required', 'image', 'max:5120'],
        ]);

        $imagePath = $request->file('image')->store('carousel', 'public');

        CarouselImage::create([
            'title' => $validated['title'],
            'image_path' => $imagePath,
        ]);

        return back()->with('success', 'Carousel image added successfully.');
    }

    public function updateCarousel(Request $request, CarouselImage $carousel): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        $imagePath = $carousel->image_path;

        if ($request->hasFile('image')) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            $imagePath = $request->file('image')->store('carousel', 'public');
        }

        $carousel->update([
            'title' => $validated['title'],
            'image_path' => $imagePath,
        ]);

        return back()->with('success', 'Carousel image updated successfully.');
    }

    public function destroyCarousel(CarouselImage $carousel): RedirectResponse
    {
        if ($carousel->image_path) {
            Storage::disk('public')->delete($carousel->image_path);
        }

        $carousel->delete();

        return to_route('admin.carousel', [], 303)
            ->with('success', 'Carousel image deleted successfully.');
    }

    public function storeVideo(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'youtube_link' => ['required', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        FeaturedVideo::create($validated);

        return back()->with('success', 'Featured video added successfully.');
    }

    public function updateVideo(Request $request, FeaturedVideo $video): RedirectResponse
    {
        $validated = $request->validate([
            'youtube_link' => ['required', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $video->update($validated);

        return back()->with('success', 'Featured video updated successfully.');
    }

    public function destroyVideo(FeaturedVideo $video): RedirectResponse
    {
        $video->delete();

        return to_route('admin.videos', [], 303)
            ->with('success', 'Featured video deleted successfully.');
    }

    public function storeAnnouncement(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
        ]);

        Announcement::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
        ]);

        return back()->with('success', 'Announcement published successfully.');
    }

    public function updateAnnouncement(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $announcement->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
        ]);

        return back()->with('success', 'Announcement updated successfully.');
    }

    public function destroyAnnouncement(Announcement $announcement): RedirectResponse
    {
        if ($announcement->image_path) {
            Storage::disk('public')->delete($announcement->image_path);
        }

        $announcement->delete();

        return to_route('admin.announcements', [], 303)
            ->with('success', 'Announcement deleted successfully.');
    }

    public function analytics(Request $request): Response
    {
        $filters = [
            'district' => trim((string) $request->query('district', '')),
            'school' => trim((string) $request->query('school', '')),
            'category' => trim((string) $request->query('category', '')),
            'search' => trim((string) $request->query('search', '')),
        ];

        $emptyAnalyticsPayload = [
            'stats' => [
                'total_downloads' => 0,
                'total_file_opens' => 0,
                'total_folder_opens' => 0,
                'active_users' => 0,
                'storage_used' => '0 MB',
            ],
            'kpis' => [
                'totalTeachers' => 0,
                'totalSchools' => 0,
                'totalDistricts' => 0,
                'totalFolders' => 0,
                'totalResources' => 0,
                'totalDownloads' => 0,
                'totalOpens' => 0,
                'totalLockedResources' => 0,
            ],
            'teacherAnalytics' => [
                'topActiveTeachers' => [],
                'opensVsDownloads' => [
                    'opens' => 0,
                    'downloads' => 0,
                ],
                'monthlyTrend' => [],
                'leastActiveTeachers' => [],
                'noActivityTeachers' => [],
            ],
            'districtAnalytics' => [
                'mostActiveDistricts' => [],
                'mostActiveSchools' => [],
                'leaderboard' => [],
                'heatmap' => [],
            ],
            'resourceAnalytics' => [
                'topDownloadedResources' => [],
                'topOpenedResources' => [],
                'categoryPopularity' => [],
                'monthlyUsageTrend' => [],
                'neverOpenedResources' => [],
                'lockedHighActivityResources' => [],
            ],
            'materialAnalytics' => [
                'stats' => [
                    'totalMaterials' => 0,
                    'totalSubmissions' => 0,
                    'totalQuantity' => 0,
                    'teachersWithInventory' => 0,
                    'schoolsWithInventory' => 0,
                    'districtsWithInventory' => 0,
                ],
                'teacherHoldings' => [],
                'schoolHoldings' => [],
                'resourceTypeBreakdown' => [],
                'learningAreaBreakdown' => [],
                'gradeLevelBreakdown' => [],
                'monthlyInventoryTrend' => [],
                'topMaterialsByQuantity' => [],
                'unreportedMaterials' => [],
            ],
            'filters' => $filters,
            'filterOptions' => [
                'districts' => [],
                'schools' => [],
                'categories' => [],
            ],
            'districtStats' => [],
            'schoolStats' => [],
            'topFolders' => [],
            'topFiles' => [],
            'recentActivity' => [],
            'usersByRole' => [],
            'loadError' => null,
        ];

        if (
            ! Schema::hasTable('resource_trackings') ||
            ! Schema::hasTable('users') ||
            ! Schema::hasTable('resource_files')
        ) {
            return Inertia::render('Admin/Resources/Analytics', $emptyAnalyticsPayload);
        }

        $hasRoleColumn = Schema::hasColumn('users', 'role');
        $hasDistrictColumn = Schema::hasColumn('users', 'district');
        $hasSchoolColumn = Schema::hasColumn('users', 'school_name');
        $hasCategoryColumn = Schema::hasColumn('resource_files', 'category');
        $hasLockedColumn = Schema::hasColumn('resource_files', 'is_locked');
        $hasFoldersTable = Schema::hasTable('folders');
        $hasLearningMaterialsTable = Schema::hasTable('learning_materials');
        $hasLearningMaterialInventoriesTable = Schema::hasTable('learning_material_inventories');
        $hasLearningAreaColumn = $hasLearningMaterialsTable && Schema::hasColumn('learning_materials', 'learning_area');
        $hasGradeLevelColumn = $hasLearningMaterialsTable && Schema::hasColumn('learning_materials', 'grade_level');
        $hasResourceTypeColumn = $hasLearningMaterialsTable && Schema::hasColumn('learning_materials', 'resource_type');
        $hasAuthorColumn = $hasLearningMaterialsTable && Schema::hasColumn('learning_materials', 'author');
        $hasPublisherColumn = $hasLearningMaterialsTable && Schema::hasColumn('learning_materials', 'publisher');

        if ($hasDistrictColumn) {
            $emptyAnalyticsPayload['filterOptions']['districts'] = User::query()
                ->when($hasRoleColumn, fn ($query) => $query->where('role', 'teacher'))
                ->whereNotNull('district')
                ->where('district', '!=', '')
                ->distinct('district')
                ->orderBy('district')
                ->pluck('district')
                ->values();
        }

        if ($hasSchoolColumn) {
            $emptyAnalyticsPayload['filterOptions']['schools'] = User::query()
                ->when($hasRoleColumn, fn ($query) => $query->where('role', 'teacher'))
                ->when($hasDistrictColumn && $filters['district'] !== '', fn ($query) => $query->where('district', $filters['district']))
                ->whereNotNull('school_name')
                ->where('school_name', '!=', '')
                ->distinct('school_name')
                ->orderBy('school_name')
                ->pluck('school_name')
                ->values();
        }

        if ($hasCategoryColumn) {
            $emptyAnalyticsPayload['filterOptions']['categories'] = ResourceFile::query()
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->distinct('category')
                ->orderBy('category')
                ->pluck('category')
                ->values();
        }

        try {
            $search = $filters['search'];
            $searchLike = '%'.$search.'%';
            $hasSearchFilter = $search !== '';
            $hasDistrictFilter = $filters['district'] !== '';
            $hasSchoolFilter = $filters['school'] !== '';
            $hasCategoryFilter = $filters['category'] !== '';

            $applyTeacherFilterOnUsers = function ($query) use (
                $hasRoleColumn,
                $hasDistrictColumn,
                $hasSchoolColumn,
                $hasDistrictFilter,
                $hasSchoolFilter,
                $filters,
                $hasSearchFilter,
                $searchLike
            ): void {
                if ($hasRoleColumn) {
                    $query->where('users.role', 'teacher');
                }

                if ($hasDistrictColumn && $hasDistrictFilter) {
                    $query->where('users.district', $filters['district']);
                }

                if ($hasSchoolColumn && $hasSchoolFilter) {
                    $query->where('users.school_name', $filters['school']);
                }

                if ($hasSearchFilter) {
                    $query->where(function ($nested) use ($hasDistrictColumn, $hasSchoolColumn, $searchLike) {
                        $nested->where('users.name', 'like', $searchLike)
                            ->orWhere('users.email', 'like', $searchLike);

                        if ($hasDistrictColumn) {
                            $nested->orWhere('users.district', 'like', $searchLike);
                        }

                        if ($hasSchoolColumn) {
                            $nested->orWhere('users.school_name', 'like', $searchLike);
                        }
                    });
                }
            };

            $applyTeacherFilterOnTrackings = function ($query) use (
                $hasRoleColumn,
                $hasDistrictColumn,
                $hasSchoolColumn,
                $hasCategoryColumn,
                $hasDistrictFilter,
                $hasSchoolFilter,
                $hasCategoryFilter,
                $filters,
                $hasSearchFilter,
                $searchLike
            ): void {
                if ($hasRoleColumn) {
                    $query->where('users.role', 'teacher');
                }

                if ($hasDistrictColumn && $hasDistrictFilter) {
                    $query->where('users.district', $filters['district']);
                }

                if ($hasSchoolColumn && $hasSchoolFilter) {
                    $query->where('users.school_name', $filters['school']);
                }

                if ($hasCategoryColumn && $hasCategoryFilter) {
                    $query->whereExists(function ($subQuery) use ($filters) {
                        $subQuery->selectRaw('1')
                            ->from('resource_files')
                            ->whereColumn('resource_files.id', 'resource_trackings.resource_file_id')
                            ->where('resource_files.category', $filters['category']);
                    });
                }

                if ($hasSearchFilter) {
                    $query->where(function ($nested) use ($hasDistrictColumn, $hasSchoolColumn, $searchLike) {
                        $nested->where('users.name', 'like', $searchLike)
                            ->orWhere('users.email', 'like', $searchLike)
                            ->orWhere('resource_trackings.action', 'like', $searchLike);

                        if ($hasDistrictColumn) {
                            $nested->orWhere('users.district', 'like', $searchLike);
                        }

                        if ($hasSchoolColumn) {
                            $nested->orWhere('users.school_name', 'like', $searchLike);
                        }
                    });
                }
            };

            $materialLearningAreaExpr = $hasLearningAreaColumn
                ? "COALESCE(NULLIF(learning_materials.learning_area, ''), 'Unspecified')"
                : "'Unspecified'";
            $materialGradeLevelExpr = $hasGradeLevelColumn
                ? "COALESCE(NULLIF(learning_materials.grade_level, ''), 'Unspecified')"
                : "'Unspecified'";
            $materialResourceTypeExpr = $hasResourceTypeColumn
                ? "COALESCE(NULLIF(learning_materials.resource_type, ''), 'Unspecified')"
                : "'Unspecified'";
            $materialAuthorExpr = $hasAuthorColumn
                ? "COALESCE(NULLIF(learning_materials.author, ''), 'N/A')"
                : "'N/A'";
            $materialPublisherExpr = $hasPublisherColumn
                ? "COALESCE(NULLIF(learning_materials.publisher, ''), 'N/A')"
                : "'N/A'";
            $materialInventoryMonthExpr = $this->monthBucketExpression('learning_material_inventories.updated_at');

            $applyMaterialInventoryFilters = function ($query) use (
                $hasRoleColumn,
                $hasDistrictColumn,
                $hasSchoolColumn,
                $hasDistrictFilter,
                $hasSchoolFilter,
                $filters,
                $hasSearchFilter,
                $searchLike,
                $hasAuthorColumn,
                $hasLearningAreaColumn,
                $hasGradeLevelColumn,
                $hasResourceTypeColumn,
                $hasPublisherColumn
            ): void {
                if ($hasRoleColumn) {
                    $query->where('users.role', 'teacher');
                }

                if ($hasDistrictColumn && $hasDistrictFilter) {
                    $query->where('users.district', $filters['district']);
                }

                if ($hasSchoolColumn && $hasSchoolFilter) {
                    $query->where('users.school_name', $filters['school']);
                }

                if ($hasSearchFilter) {
                    $query->where(function ($nested) use (
                        $searchLike,
                        $hasDistrictColumn,
                        $hasSchoolColumn,
                        $hasAuthorColumn,
                        $hasLearningAreaColumn,
                        $hasGradeLevelColumn,
                        $hasResourceTypeColumn,
                        $hasPublisherColumn
                    ) {
                        $nested->where('users.name', 'like', $searchLike)
                            ->orWhere('users.email', 'like', $searchLike)
                            ->orWhere('learning_materials.name', 'like', $searchLike);

                        if ($hasDistrictColumn) {
                            $nested->orWhere('users.district', 'like', $searchLike);
                        }

                        if ($hasSchoolColumn) {
                            $nested->orWhere('users.school_name', 'like', $searchLike);
                        }

                        if ($hasAuthorColumn) {
                            $nested->orWhere('learning_materials.author', 'like', $searchLike);
                        }

                        if ($hasLearningAreaColumn) {
                            $nested->orWhere('learning_materials.learning_area', 'like', $searchLike);
                        }

                        if ($hasGradeLevelColumn) {
                            $nested->orWhere('learning_materials.grade_level', 'like', $searchLike);
                        }

                        if ($hasResourceTypeColumn) {
                            $nested->orWhere('learning_materials.resource_type', 'like', $searchLike);
                        }

                        if ($hasPublisherColumn) {
                            $nested->orWhere('learning_materials.publisher', 'like', $searchLike);
                        }
                    });
                }
            };

            $folderOpenActions = self::TRACKING_FOLDER_OPEN_ACTIONS;
            $fileOpenActions = self::TRACKING_FILE_OPEN_ACTIONS;
            $fileDownloadActions = self::TRACKING_FILE_DOWNLOAD_ACTIONS;
            $fileActivityActions = array_values(array_unique(array_merge($fileOpenActions, $fileDownloadActions)));

            $folderOpenActionsSql = $this->sqlList($folderOpenActions);
            $fileOpenActionsSql = $this->sqlList($fileOpenActions);
            $fileDownloadActionsSql = $this->sqlList($fileDownloadActions);
            $fileActivityActionsSql = $this->sqlList($fileActivityActions);

            $trackingTotalsQuery = ResourceTracking::query()
                ->join('users', 'users.id', '=', 'resource_trackings.user_id');
            $applyTeacherFilterOnTrackings($trackingTotalsQuery);

            $totalDownloads = (clone $trackingTotalsQuery)
                ->whereIn('resource_trackings.action', $fileDownloadActions)
                ->count();
            $totalFileOpens = (clone $trackingTotalsQuery)
                ->whereIn('resource_trackings.action', $fileOpenActions)
                ->count();
            $totalFolderOpens = (clone $trackingTotalsQuery)
                ->whereIn('resource_trackings.action', $folderOpenActions)
                ->count();
            $activeUsers = (clone $trackingTotalsQuery)
                ->distinct('resource_trackings.user_id')
                ->count('resource_trackings.user_id');
            $totalResourcesQuery = ResourceFile::query();
            if ($hasCategoryColumn && $hasCategoryFilter) {
                $totalResourcesQuery->where('category', $filters['category']);
            }
            $totalResources = $totalResourcesQuery->count();

            $totalLockedResources = $hasLockedColumn
                ? ResourceFile::query()
                    ->when($hasCategoryColumn && $hasCategoryFilter, fn ($query) => $query->where('category', $filters['category']))
                    ->where('is_locked', true)
                    ->count()
                : 0;
            $totalFolders = $hasFoldersTable ? Folder::count() : 0;

            $teacherQuery = User::query()
                ->when($hasRoleColumn, fn ($query) => $query->where('role', 'teacher'));

            if ($hasDistrictColumn && $hasDistrictFilter) {
                $teacherQuery->where('district', $filters['district']);
            }

            if ($hasSchoolColumn && $hasSchoolFilter) {
                $teacherQuery->where('school_name', $filters['school']);
            }

            if ($hasSearchFilter) {
                $teacherQuery->where(function ($query) use ($hasDistrictColumn, $hasSchoolColumn, $searchLike) {
                    $query->where('name', 'like', $searchLike)
                        ->orWhere('email', 'like', $searchLike);

                    if ($hasDistrictColumn) {
                        $query->orWhere('district', 'like', $searchLike);
                    }

                    if ($hasSchoolColumn) {
                        $query->orWhere('school_name', 'like', $searchLike);
                    }
                });
            }

            $totalTeachers = (clone $teacherQuery)->count();
            $totalSchools = $hasSchoolColumn
                ? (clone $teacherQuery)
                    ->whereNotNull('school_name')
                    ->where('school_name', '!=', '')
                    ->distinct('school_name')
                    ->count('school_name')
                : 0;
            $totalDistricts = $hasDistrictColumn
                ? (clone $teacherQuery)
                    ->whereNotNull('district')
                    ->where('district', '!=', '')
                    ->distinct('district')
                    ->count('district')
                : 0;

            $storageUsedBytes = ResourceFile::query()
                ->pluck('file_path')
                ->sum(fn ($path) => $path && Storage::disk('public')->exists($path)
                    ? Storage::disk('public')->size($path)
                    : 0);

            $districtExpr = $hasDistrictColumn
                ? "COALESCE(NULLIF(users.district, ''), 'Unknown District')"
                : "'Unknown District'";
            $schoolExpr = $hasSchoolColumn
                ? "COALESCE(NULLIF(users.school_name, ''), 'Unknown School')"
                : "'Unknown School'";
            $resourceCategoryExpr = $hasCategoryColumn
                ? "COALESCE(NULLIF(resource_files.category, ''), 'Uncategorized')"
                : "COALESCE(NULLIF(resource_files.file_type, ''), 'Unknown')";
            $monthExpr = $this->monthBucketExpression('resource_trackings.created_at');

            $districtStatsQuery = ResourceTracking::query()
                ->leftJoin('users', 'users.id', '=', 'resource_trackings.user_id');
            $applyTeacherFilterOnTrackings($districtStatsQuery);

            $districtStats = $districtStatsQuery
                ->selectRaw("{$districtExpr} as district")
                ->selectRaw('COUNT(*) as total_actions')
                ->selectRaw("SUM(CASE WHEN resource_trackings.action IN ({$folderOpenActionsSql}) THEN 1 ELSE 0 END) as folders_opened")
                ->selectRaw("SUM(CASE WHEN resource_trackings.action IN ({$fileOpenActionsSql}) THEN 1 ELSE 0 END) as files_opened")
                ->selectRaw("SUM(CASE WHEN resource_trackings.action IN ({$fileDownloadActionsSql}) THEN 1 ELSE 0 END) as files_downloaded")
                ->groupBy(DB::raw($districtExpr))
                ->orderByDesc('total_actions')
                ->get();

            $schoolStatsQuery = ResourceTracking::query()
                ->leftJoin('users', 'users.id', '=', 'resource_trackings.user_id');
            $applyTeacherFilterOnTrackings($schoolStatsQuery);

            $schoolStats = $schoolStatsQuery
                ->selectRaw("{$districtExpr} as district")
                ->selectRaw("{$schoolExpr} as school_name")
                ->selectRaw('COUNT(*) as total_actions')
                ->selectRaw("SUM(CASE WHEN resource_trackings.action IN ({$folderOpenActionsSql}) THEN 1 ELSE 0 END) as folders_opened")
                ->selectRaw("SUM(CASE WHEN resource_trackings.action IN ({$fileOpenActionsSql}) THEN 1 ELSE 0 END) as files_opened")
                ->selectRaw("SUM(CASE WHEN resource_trackings.action IN ({$fileDownloadActionsSql}) THEN 1 ELSE 0 END) as files_downloaded")
                ->groupBy(DB::raw($districtExpr), DB::raw($schoolExpr))
                ->orderByDesc('total_actions')
                ->get();

            $topFoldersQuery = ResourceTracking::query()
                ->join('users', 'users.id', '=', 'resource_trackings.user_id')
                ->leftJoin('folders', 'folders.id', '=', 'resource_trackings.folder_id')
                ->whereIn('resource_trackings.action', $folderOpenActions)
                ->whereNotNull('resource_trackings.folder_id');
            $applyTeacherFilterOnTrackings($topFoldersQuery);

            if ($hasSearchFilter) {
                $topFoldersQuery->where(function ($query) use ($searchLike) {
                    $query->where('folders.name', 'like', $searchLike)
                        ->orWhere('users.name', 'like', $searchLike);
                });
            }

            $topFolders = $topFoldersQuery
                ->selectRaw("COALESCE(NULLIF(folders.name, ''), 'Unknown Folder') as folder_name")
                ->selectRaw('COUNT(*) as total')
                ->groupBy(DB::raw("COALESCE(NULLIF(folders.name, ''), 'Unknown Folder')"))
                ->orderByDesc('total')
                ->limit(10)
                ->get()
                ->map(fn ($row) => [
                    'folder_name' => $row->folder_name,
                    'total' => (int) $row->total,
                ])
                ->values();

            $topFilesQuery = ResourceTracking::query()
                ->join('users', 'users.id', '=', 'resource_trackings.user_id')
                ->leftJoin('resource_files', 'resource_files.id', '=', 'resource_trackings.resource_file_id')
                ->whereIn('resource_trackings.action', $fileActivityActions)
                ->whereNotNull('resource_trackings.resource_file_id');
            $applyTeacherFilterOnTrackings($topFilesQuery);

            if ($hasSearchFilter) {
                $topFilesQuery->where(function ($query) use ($searchLike) {
                    $query->where('resource_files.title', 'like', $searchLike)
                        ->orWhere('users.name', 'like', $searchLike);
                });
            }

            $topFiles = $topFilesQuery
                ->selectRaw("COALESCE(NULLIF(resource_files.title, ''), 'Unknown File') as file_title")
                ->selectRaw('COUNT(*) as total')
                ->groupBy(DB::raw("COALESCE(NULLIF(resource_files.title, ''), 'Unknown File')"))
                ->orderByDesc('total')
                ->limit(10)
                ->get()
                ->map(fn ($row) => [
                    'file_title' => $row->file_title,
                    'total' => (int) $row->total,
                ])
                ->values();

            $topActiveTeachers = ResourceTracking::query()
                ->join('users', 'users.id', '=', 'resource_trackings.user_id')
                ->whereIn('resource_trackings.action', $fileActivityActions);
            $applyTeacherFilterOnTrackings($topActiveTeachers);

            $topActiveTeachers = $topActiveTeachers
                ->select('users.id')
                ->selectRaw("COALESCE(NULLIF(users.name, ''), 'Unknown Teacher') as teacher_name")
                ->selectRaw("COALESCE(NULLIF(users.email, ''), 'N/A') as teacher_email")
                ->selectRaw("{$districtExpr} as district")
                ->selectRaw("{$schoolExpr} as school_name")
                ->selectRaw("SUM(CASE WHEN resource_trackings.action IN ({$fileOpenActionsSql}) THEN 1 ELSE 0 END) as opens")
                ->selectRaw("SUM(CASE WHEN resource_trackings.action IN ({$fileDownloadActionsSql}) THEN 1 ELSE 0 END) as downloads")
                ->selectRaw("SUM(CASE WHEN resource_trackings.action IN ({$fileActivityActionsSql}) THEN 1 ELSE 0 END) as total_activity")
                ->groupBy(
                    'users.id',
                    DB::raw("COALESCE(NULLIF(users.name, ''), 'Unknown Teacher')"),
                    DB::raw("COALESCE(NULLIF(users.email, ''), 'N/A')"),
                    DB::raw($districtExpr),
                    DB::raw($schoolExpr),
                )
                ->orderByDesc('total_activity')
                ->limit(10)
                ->get()
                ->map(fn ($row) => [
                    'teacherName' => $row->teacher_name,
                    'teacherEmail' => $row->teacher_email,
                    'district' => $row->district,
                    'school' => $row->school_name,
                    'opens' => (int) $row->opens,
                    'downloads' => (int) $row->downloads,
                    'totalActivity' => (int) $row->total_activity,
                ])
                ->values();

            $teacherActivitySummaryQuery = User::query()
                ->leftJoin('resource_trackings', function ($join) use ($fileActivityActions) {
                    $join->on('users.id', '=', 'resource_trackings.user_id')
                        ->whereIn('resource_trackings.action', $fileActivityActions);
                });
            $applyTeacherFilterOnUsers($teacherActivitySummaryQuery);

            $teacherActivitySummary = $teacherActivitySummaryQuery
                ->select('users.id')
                ->selectRaw("COALESCE(NULLIF(users.name, ''), 'Unknown Teacher') as teacher_name")
                ->selectRaw("COALESCE(NULLIF(users.email, ''), 'N/A') as teacher_email")
                ->selectRaw("{$districtExpr} as district")
                ->selectRaw("{$schoolExpr} as school_name")
                ->selectRaw("SUM(CASE WHEN resource_trackings.action IN ({$fileOpenActionsSql}) THEN 1 ELSE 0 END) as opens")
                ->selectRaw("SUM(CASE WHEN resource_trackings.action IN ({$fileDownloadActionsSql}) THEN 1 ELSE 0 END) as downloads")
                ->selectRaw("SUM(CASE WHEN resource_trackings.action IN ({$fileActivityActionsSql}) THEN 1 ELSE 0 END) as total_activity")
                ->groupBy(
                    'users.id',
                    DB::raw("COALESCE(NULLIF(users.name, ''), 'Unknown Teacher')"),
                    DB::raw("COALESCE(NULLIF(users.email, ''), 'N/A')"),
                    DB::raw($districtExpr),
                    DB::raw($schoolExpr),
                )
                ->get()
                ->map(fn ($row) => [
                    'teacherName' => $row->teacher_name,
                    'teacherEmail' => $row->teacher_email,
                    'district' => $row->district,
                    'school' => $row->school_name,
                    'opens' => (int) $row->opens,
                    'downloads' => (int) $row->downloads,
                    'totalActivity' => (int) $row->total_activity,
                ])
                ->values();

            $leastActiveTeachers = $teacherActivitySummary
                ->filter(fn ($row) => $row['totalActivity'] > 0)
                ->sortBy('totalActivity')
                ->take(10)
                ->values();

            $noActivityTeachers = $teacherActivitySummary
                ->filter(fn ($row) => $row['totalActivity'] === 0)
                ->sortBy('teacherName')
                ->take(10)
                ->values();

            $teacherTrendDateExpr = 'DATE(resource_trackings.created_at)';

            $teacherMonthlyTrend = ResourceTracking::query()
                ->join('users', 'users.id', '=', 'resource_trackings.user_id')
                ->whereIn('resource_trackings.action', $fileActivityActions);
            $applyTeacherFilterOnTrackings($teacherMonthlyTrend);

            $teacherMonthlyTrend = $teacherMonthlyTrend
                ->selectRaw("{$teacherTrendDateExpr} as date_key")
                ->selectRaw("SUM(CASE WHEN resource_trackings.action IN ({$fileOpenActionsSql}) THEN 1 ELSE 0 END) as opens")
                ->selectRaw("SUM(CASE WHEN resource_trackings.action IN ({$fileDownloadActionsSql}) THEN 1 ELSE 0 END) as downloads")
                ->groupBy(DB::raw($teacherTrendDateExpr))
                ->orderBy('date_key')
                ->get()
                ->map(fn ($row) => [
                    'dateKey' => (string) $row->date_key,
                    'label' => (string) $row->date_key,
                    'opens' => (int) $row->opens,
                    'downloads' => (int) $row->downloads,
                ])
                ->values();

            $schoolTeacherCountsQuery = User::query();
            $applyTeacherFilterOnUsers($schoolTeacherCountsQuery);

            $schoolTeacherCounts = $schoolTeacherCountsQuery
                ->selectRaw("{$districtExpr} as district")
                ->selectRaw("{$schoolExpr} as school_name")
                ->selectRaw('COUNT(*) as total_teachers')
                ->groupBy(DB::raw($districtExpr), DB::raw($schoolExpr))
                ->get();

            $schoolActivityQuery = ResourceTracking::query()
                ->join('users', 'users.id', '=', 'resource_trackings.user_id')
                ->whereIn('resource_trackings.action', $fileActivityActions);
            $applyTeacherFilterOnTrackings($schoolActivityQuery);

            $schoolActivity = $schoolActivityQuery
                ->selectRaw("{$districtExpr} as district")
                ->selectRaw("{$schoolExpr} as school_name")
                ->selectRaw("SUM(CASE WHEN resource_trackings.action IN ({$fileOpenActionsSql}) THEN 1 ELSE 0 END) as opens")
                ->selectRaw("SUM(CASE WHEN resource_trackings.action IN ({$fileDownloadActionsSql}) THEN 1 ELSE 0 END) as downloads")
                ->selectRaw("SUM(CASE WHEN resource_trackings.action IN ({$fileActivityActionsSql}) THEN 1 ELSE 0 END) as total_activity")
                ->groupBy(DB::raw($districtExpr), DB::raw($schoolExpr))
                ->get();

            $schoolPerformanceMap = [];

            foreach ($schoolTeacherCounts as $row) {
                $key = "{$row->district}::{$row->school_name}";
                $schoolPerformanceMap[$key] = [
                    'district' => $row->district,
                    'school' => $row->school_name,
                    'totalTeachers' => (int) $row->total_teachers,
                    'opens' => 0,
                    'downloads' => 0,
                    'totalActivity' => 0,
                ];
            }

            foreach ($schoolActivity as $row) {
                $key = "{$row->district}::{$row->school_name}";
                if (! isset($schoolPerformanceMap[$key])) {
                    $schoolPerformanceMap[$key] = [
                        'district' => $row->district,
                        'school' => $row->school_name,
                        'totalTeachers' => 0,
                        'opens' => 0,
                        'downloads' => 0,
                        'totalActivity' => 0,
                    ];
                }

                $schoolPerformanceMap[$key]['opens'] = (int) $row->opens;
                $schoolPerformanceMap[$key]['downloads'] = (int) $row->downloads;
                $schoolPerformanceMap[$key]['totalActivity'] = (int) $row->total_activity;
            }

            $schoolPerformance = collect(array_values($schoolPerformanceMap))
                ->sortByDesc('totalActivity')
                ->values();

            $districtPerformance = $schoolPerformance
                ->groupBy('district')
                ->map(fn ($rows, $district) => [
                    'district' => $district,
                    'totalTeachers' => (int) $rows->sum('totalTeachers'),
                    'opens' => (int) $rows->sum('opens'),
                    'downloads' => (int) $rows->sum('downloads'),
                    'totalActivity' => (int) $rows->sum('totalActivity'),
                ])
                ->sortByDesc('totalActivity')
                ->values();

            $topDownloadedResourcesQuery = ResourceTracking::query()
                ->join('users', 'users.id', '=', 'resource_trackings.user_id')
                ->leftJoin('resource_files', 'resource_files.id', '=', 'resource_trackings.resource_file_id')
                ->whereIn('resource_trackings.action', $fileDownloadActions)
                ->whereNotNull('resource_trackings.resource_file_id');
            $applyTeacherFilterOnTrackings($topDownloadedResourcesQuery);

            if ($hasSearchFilter) {
                $topDownloadedResourcesQuery->where(function ($query) use ($searchLike) {
                    $query->where('resource_files.title', 'like', $searchLike)
                        ->orWhere('users.name', 'like', $searchLike);
                });
            }

            $topDownloadedResources = $topDownloadedResourcesQuery
                ->selectRaw("COALESCE(NULLIF(resource_files.title, ''), 'Unknown Resource') as title")
                ->selectRaw('COUNT(*) as downloads')
                ->groupBy(DB::raw("COALESCE(NULLIF(resource_files.title, ''), 'Unknown Resource')"))
                ->orderByDesc('downloads')
                ->limit(10)
                ->get()
                ->map(fn ($row) => [
                    'title' => $row->title,
                    'downloads' => (int) $row->downloads,
                ])
                ->values();

            $topOpenedResourcesQuery = ResourceTracking::query()
                ->join('users', 'users.id', '=', 'resource_trackings.user_id')
                ->leftJoin('resource_files', 'resource_files.id', '=', 'resource_trackings.resource_file_id')
                ->whereIn('resource_trackings.action', $fileOpenActions)
                ->whereNotNull('resource_trackings.resource_file_id');
            $applyTeacherFilterOnTrackings($topOpenedResourcesQuery);

            if ($hasSearchFilter) {
                $topOpenedResourcesQuery->where(function ($query) use ($searchLike) {
                    $query->where('resource_files.title', 'like', $searchLike)
                        ->orWhere('users.name', 'like', $searchLike);
                });
            }

            $topOpenedResources = $topOpenedResourcesQuery
                ->selectRaw("COALESCE(NULLIF(resource_files.title, ''), 'Unknown Resource') as title")
                ->selectRaw('COUNT(*) as opens')
                ->groupBy(DB::raw("COALESCE(NULLIF(resource_files.title, ''), 'Unknown Resource')"))
                ->orderByDesc('opens')
                ->limit(10)
                ->get()
                ->map(fn ($row) => [
                    'title' => $row->title,
                    'opens' => (int) $row->opens,
                ])
                ->values();

            $categoryPopularityQuery = ResourceTracking::query()
                ->join('users', 'users.id', '=', 'resource_trackings.user_id')
                ->join('resource_files', 'resource_files.id', '=', 'resource_trackings.resource_file_id')
                ->whereIn('resource_trackings.action', $fileActivityActions);
            $applyTeacherFilterOnTrackings($categoryPopularityQuery);

            if ($hasSearchFilter) {
                $categoryPopularityQuery->where(function ($query) use ($searchLike) {
                    $query->where('resource_files.title', 'like', $searchLike)
                        ->orWhere('resource_files.category', 'like', $searchLike)
                        ->orWhere('resource_files.file_type', 'like', $searchLike);
                });
            }

            $rawCategoryPopularity = $categoryPopularityQuery
                ->selectRaw("{$resourceCategoryExpr} as category")
                ->selectRaw('COUNT(*) as total')
                ->groupBy(DB::raw($resourceCategoryExpr))
                ->orderByDesc('total')
                ->get()
                ->mapWithKeys(fn ($row) => [
                    ($row->category ?: 'Uncategorized') => (int) $row->total,
                ]);

            // Show every category (even if 0 usage) so the doughnut chart doesn't hide
            // newly uploaded resource types that haven't been opened/downloaded yet.
            $knownCategories = collect(self::RESOURCE_CATEGORIES)
                ->merge(['Uncategorized'])
                ->values();

            $fileCategories = ResourceFile::query()
                ->selectRaw("{$resourceCategoryExpr} as category")
                ->when($hasCategoryColumn && $hasCategoryFilter, fn ($query) => $query->where('resource_files.category', $filters['category']))
                ->distinct()
                ->pluck('category')
                ->map(fn ($value) => $value ?: 'Uncategorized')
                ->values();

            $allCategories = $knownCategories
                ->merge($fileCategories)
                ->map(fn ($value) => $value ?: 'Uncategorized')
                ->unique()
                ->values();
            if ($hasCategoryFilter) {
                $allCategories = $allCategories
                    ->filter(fn ($value) => $value === $filters['category'])
                    ->values();
            }

            $categoryPopularity = $allCategories
                ->map(fn ($category) => [
                    'category' => $category,
                    'total' => (int) ($rawCategoryPopularity[$category] ?? 0),
                ])
                ->sortByDesc('total')
                ->values();

            $monthlyResourceUsageQuery = ResourceTracking::query()
                ->join('users', 'users.id', '=', 'resource_trackings.user_id')
                ->leftJoin('resource_files', 'resource_files.id', '=', 'resource_trackings.resource_file_id')
                ->whereIn('resource_trackings.action', $fileActivityActions);
            $applyTeacherFilterOnTrackings($monthlyResourceUsageQuery);

            if ($hasSearchFilter) {
                $monthlyResourceUsageQuery->where(function ($query) use ($searchLike) {
                    $query->where('resource_files.title', 'like', $searchLike)
                        ->orWhere('users.name', 'like', $searchLike);
                });
            }

            $monthlyResourceUsage = $monthlyResourceUsageQuery
                ->selectRaw("{$monthExpr} as month_key")
                ->selectRaw("SUM(CASE WHEN resource_trackings.action IN ({$fileOpenActionsSql}) THEN 1 ELSE 0 END) as opens")
                ->selectRaw("SUM(CASE WHEN resource_trackings.action IN ({$fileDownloadActionsSql}) THEN 1 ELSE 0 END) as downloads")
                ->groupBy(DB::raw($monthExpr))
                ->orderBy('month_key')
                ->get()
                ->map(fn ($row) => [
                    'monthKey' => (string) $row->month_key,
                    'label' => $this->monthKeyToLabel((string) $row->month_key),
                    'opens' => (int) $row->opens,
                    'downloads' => (int) $row->downloads,
                ])
                ->values();

            $neverOpenedResourcesQuery = ResourceFile::query()
                ->leftJoin('folders', 'folders.id', '=', 'resource_files.folder_id')
                ->leftJoin('resource_trackings as open_trackings', function ($join) use ($fileOpenActions) {
                    $join->on('open_trackings.resource_file_id', '=', 'resource_files.id')
                        ->whereIn('open_trackings.action', $fileOpenActions);
                })
                ->leftJoin('users as open_users', 'open_users.id', '=', 'open_trackings.user_id');

            if ($hasRoleColumn) {
                $neverOpenedResourcesQuery->where(function ($query) {
                    $query->where('open_users.role', 'teacher')
                        ->orWhereNull('open_users.id');
                });
            }

            if ($hasDistrictColumn && $hasDistrictFilter) {
                $neverOpenedResourcesQuery->where(function ($query) use ($filters) {
                    $query->where('open_users.district', $filters['district'])
                        ->orWhereNull('open_users.id');
                });
            }

            if ($hasSchoolColumn && $hasSchoolFilter) {
                $neverOpenedResourcesQuery->where(function ($query) use ($filters) {
                    $query->where('open_users.school_name', $filters['school'])
                        ->orWhereNull('open_users.id');
                });
            }

            if ($hasSearchFilter) {
                $neverOpenedResourcesQuery->where(function ($query) use ($searchLike) {
                    $query->where('resource_files.title', 'like', $searchLike)
                        ->orWhere('resource_files.category', 'like', $searchLike)
                        ->orWhere('folders.name', 'like', $searchLike);
                });
            }
            if ($hasCategoryColumn && $hasCategoryFilter) {
                $neverOpenedResourcesQuery->where('resource_files.category', $filters['category']);
            }

            $neverOpenedResources = $neverOpenedResourcesQuery
                ->select('resource_files.id', 'resource_files.title', 'resource_files.created_at')
                ->selectRaw("{$resourceCategoryExpr} as category")
                ->selectRaw("COALESCE(NULLIF(folders.name, ''), 'Unassigned Folder') as folder_name")
                ->selectRaw('COUNT(open_trackings.id) as opens_count')
                ->groupBy(
                    'resource_files.id',
                    'resource_files.title',
                    'resource_files.created_at',
                    DB::raw($resourceCategoryExpr),
                    DB::raw("COALESCE(NULLIF(folders.name, ''), 'Unassigned Folder')"),
                )
                ->havingRaw('COUNT(open_trackings.id) = 0')
                ->orderByDesc('resource_files.created_at')
                ->limit(10)
                ->get()
                ->map(fn ($row) => [
                    'title' => $row->title ?: 'Untitled Resource',
                    'category' => $row->category ?: 'Uncategorized',
                    'folder' => $row->folder_name ?: 'Unassigned Folder',
                    'uploadedAt' => $row->created_at?->toDateString() ?: 'N/A',
                ])
                ->values();

            $lockedHighActivityResources = [];
            if ($hasLockedColumn) {
                $lockedHighActivityResourcesQuery = ResourceFile::query()
                    ->leftJoin('resource_trackings', function ($join) use ($fileActivityActions) {
                        $join->on('resource_trackings.resource_file_id', '=', 'resource_files.id')
                            ->whereIn('resource_trackings.action', $fileActivityActions);
                    })
                    ->leftJoin('users', 'users.id', '=', 'resource_trackings.user_id')
                    ->where('resource_files.is_locked', true);
                $applyTeacherFilterOnTrackings($lockedHighActivityResourcesQuery);

                if ($hasSearchFilter) {
                    $lockedHighActivityResourcesQuery->where(function ($query) use ($searchLike) {
                        $query->where('resource_files.title', 'like', $searchLike)
                            ->orWhere('resource_files.category', 'like', $searchLike);
                    });
                }
                if ($hasCategoryColumn && $hasCategoryFilter) {
                    $lockedHighActivityResourcesQuery->where('resource_files.category', $filters['category']);
                }

                $lockedHighActivityResources = $lockedHighActivityResourcesQuery
                    ->select('resource_files.id', 'resource_files.title')
                    ->selectRaw("{$resourceCategoryExpr} as category")
                    ->selectRaw("SUM(CASE WHEN resource_trackings.action IN ({$fileOpenActionsSql}) THEN 1 ELSE 0 END) as opens")
                    ->selectRaw("SUM(CASE WHEN resource_trackings.action IN ({$fileDownloadActionsSql}) THEN 1 ELSE 0 END) as downloads")
                    ->selectRaw("SUM(CASE WHEN resource_trackings.action IN ({$fileActivityActionsSql}) THEN 1 ELSE 0 END) as total_activity")
                    ->groupBy(
                        'resource_files.id',
                        'resource_files.title',
                        DB::raw($resourceCategoryExpr),
                    )
                    ->havingRaw("SUM(CASE WHEN resource_trackings.action IN ({$fileActivityActionsSql}) THEN 1 ELSE 0 END) > 0")
                    ->orderByDesc('total_activity')
                    ->limit(10)
                    ->get()
                    ->map(fn ($row) => [
                        'title' => $row->title ?: 'Untitled Resource',
                        'category' => $row->category ?: 'Uncategorized',
                        'opens' => (int) $row->opens,
                        'downloads' => (int) $row->downloads,
                        'totalActivity' => (int) $row->total_activity,
                    ])
                    ->values()
                    ->all();
            }

            $materialAnalytics = $emptyAnalyticsPayload['materialAnalytics'];

            if ($hasLearningMaterialsTable && $hasLearningMaterialInventoriesTable) {
                $applyMaterialSearchOnMaterials = function ($query) use (
                    $hasSearchFilter,
                    $searchLike,
                    $hasAuthorColumn,
                    $hasLearningAreaColumn,
                    $hasGradeLevelColumn,
                    $hasResourceTypeColumn,
                    $hasPublisherColumn
                ): void {
                    if (! $hasSearchFilter) {
                        return;
                    }

                    $query->where(function ($nested) use (
                        $searchLike,
                        $hasAuthorColumn,
                        $hasLearningAreaColumn,
                        $hasGradeLevelColumn,
                        $hasResourceTypeColumn,
                        $hasPublisherColumn
                    ) {
                        $nested->where('learning_materials.name', 'like', $searchLike);

                        if ($hasAuthorColumn) {
                            $nested->orWhere('learning_materials.author', 'like', $searchLike);
                        }

                        if ($hasLearningAreaColumn) {
                            $nested->orWhere('learning_materials.learning_area', 'like', $searchLike);
                        }

                        if ($hasGradeLevelColumn) {
                            $nested->orWhere('learning_materials.grade_level', 'like', $searchLike);
                        }

                        if ($hasResourceTypeColumn) {
                            $nested->orWhere('learning_materials.resource_type', 'like', $searchLike);
                        }

                        if ($hasPublisherColumn) {
                            $nested->orWhere('learning_materials.publisher', 'like', $searchLike);
                        }
                    });
                };

                $materialInventoryBaseQuery = LearningMaterialInventory::query()
                    ->join('learning_materials', 'learning_materials.id', '=', 'learning_material_inventories.learning_material_id')
                    ->join('users', 'users.id', '=', 'learning_material_inventories.user_id');

                $applyMaterialInventoryFilters($materialInventoryBaseQuery);

                $materialTotalSubmissions = (clone $materialInventoryBaseQuery)->count('learning_material_inventories.id');
                $materialTotalQuantity = (int) (clone $materialInventoryBaseQuery)->sum('learning_material_inventories.quantity');
                $materialTeachersWithInventory = (clone $materialInventoryBaseQuery)
                    ->distinct('learning_material_inventories.user_id')
                    ->count('learning_material_inventories.user_id');
                $materialSchoolsWithInventory = $hasSchoolColumn
                    ? (clone $materialInventoryBaseQuery)
                        ->whereNotNull('users.school_name')
                        ->where('users.school_name', '!=', '')
                        ->distinct('users.school_name')
                        ->count('users.school_name')
                    : 0;
                $materialDistrictsWithInventory = $hasDistrictColumn
                    ? (clone $materialInventoryBaseQuery)
                        ->whereNotNull('users.district')
                        ->where('users.district', '!=', '')
                        ->distinct('users.district')
                        ->count('users.district')
                    : 0;

                $materialTotalMaterials = 0;
                if ($hasDistrictFilter || $hasSchoolFilter) {
                    $materialTotalMaterials = (clone $materialInventoryBaseQuery)
                        ->distinct('learning_material_inventories.learning_material_id')
                        ->count('learning_material_inventories.learning_material_id');
                } else {
                    $materialCatalogCountQuery = LearningMaterial::query();
                    $applyMaterialSearchOnMaterials($materialCatalogCountQuery);
                    $materialTotalMaterials = $materialCatalogCountQuery->count();
                }

                $materialTeacherHoldings = LearningMaterialInventory::query()
                    ->join('learning_materials', 'learning_materials.id', '=', 'learning_material_inventories.learning_material_id')
                    ->join('users', 'users.id', '=', 'learning_material_inventories.user_id');
                $applyMaterialInventoryFilters($materialTeacherHoldings);

                $materialTeacherHoldings = $materialTeacherHoldings
                    ->select('users.id')
                    ->selectRaw("COALESCE(NULLIF(users.name, ''), 'Unknown Teacher') as teacher_name")
                    ->selectRaw("COALESCE(NULLIF(users.email, ''), 'N/A') as teacher_email")
                    ->selectRaw("{$districtExpr} as district")
                    ->selectRaw("{$schoolExpr} as school_name")
                    ->selectRaw('COUNT(DISTINCT learning_material_inventories.learning_material_id) as total_materials')
                    ->selectRaw('SUM(learning_material_inventories.quantity) as total_quantity')
                    ->groupBy(
                        'users.id',
                        DB::raw("COALESCE(NULLIF(users.name, ''), 'Unknown Teacher')"),
                        DB::raw("COALESCE(NULLIF(users.email, ''), 'N/A')"),
                        DB::raw($districtExpr),
                        DB::raw($schoolExpr),
                    )
                    ->orderByDesc('total_quantity')
                    ->orderByDesc('total_materials')
                    ->limit(10)
                    ->get()
                    ->map(fn ($row) => [
                        'teacherName' => $row->teacher_name,
                        'teacherEmail' => $row->teacher_email,
                        'district' => $row->district,
                        'school' => $row->school_name,
                        'totalMaterials' => (int) $row->total_materials,
                        'totalQuantity' => (int) $row->total_quantity,
                    ])
                    ->values();

                $materialSchoolHoldings = LearningMaterialInventory::query()
                    ->join('learning_materials', 'learning_materials.id', '=', 'learning_material_inventories.learning_material_id')
                    ->join('users', 'users.id', '=', 'learning_material_inventories.user_id');
                $applyMaterialInventoryFilters($materialSchoolHoldings);

                $materialSchoolHoldings = $materialSchoolHoldings
                    ->selectRaw("{$districtExpr} as district")
                    ->selectRaw("{$schoolExpr} as school_name")
                    ->selectRaw('COUNT(DISTINCT learning_material_inventories.user_id) as total_teachers')
                    ->selectRaw('COUNT(DISTINCT learning_material_inventories.learning_material_id) as total_materials')
                    ->selectRaw('SUM(learning_material_inventories.quantity) as total_quantity')
                    ->groupBy(DB::raw($districtExpr), DB::raw($schoolExpr))
                    ->orderByDesc('total_quantity')
                    ->orderByDesc('total_materials')
                    ->limit(10)
                    ->get()
                    ->map(fn ($row) => [
                        'district' => $row->district,
                        'school' => $row->school_name,
                        'totalTeachers' => (int) $row->total_teachers,
                        'totalMaterials' => (int) $row->total_materials,
                        'totalQuantity' => (int) $row->total_quantity,
                    ])
                    ->values();

                $materialResourceTypeBreakdown = LearningMaterialInventory::query()
                    ->join('learning_materials', 'learning_materials.id', '=', 'learning_material_inventories.learning_material_id')
                    ->join('users', 'users.id', '=', 'learning_material_inventories.user_id');
                $applyMaterialInventoryFilters($materialResourceTypeBreakdown);

                $materialResourceTypeBreakdown = $materialResourceTypeBreakdown
                    ->selectRaw("{$materialResourceTypeExpr} as resource_type")
                    ->selectRaw('COUNT(DISTINCT learning_material_inventories.learning_material_id) as total_materials')
                    ->selectRaw('COUNT(learning_material_inventories.id) as total_submissions')
                    ->selectRaw('SUM(learning_material_inventories.quantity) as total_quantity')
                    ->groupBy(DB::raw($materialResourceTypeExpr))
                    ->orderByDesc('total_quantity')
                    ->limit(5)
                    ->get()
                    ->map(fn ($row) => [
                        'resourceType' => $row->resource_type,
                        'totalMaterials' => (int) $row->total_materials,
                        'totalSubmissions' => (int) $row->total_submissions,
                        'totalQuantity' => (int) $row->total_quantity,
                    ])
                    ->values();

                $materialLearningAreaBreakdown = LearningMaterialInventory::query()
                    ->join('learning_materials', 'learning_materials.id', '=', 'learning_material_inventories.learning_material_id')
                    ->join('users', 'users.id', '=', 'learning_material_inventories.user_id');
                $applyMaterialInventoryFilters($materialLearningAreaBreakdown);

                $materialLearningAreaBreakdown = $materialLearningAreaBreakdown
                    ->selectRaw("{$materialLearningAreaExpr} as learning_area")
                    ->selectRaw('COUNT(DISTINCT learning_material_inventories.learning_material_id) as total_materials')
                    ->selectRaw('COUNT(learning_material_inventories.id) as total_submissions')
                    ->selectRaw('SUM(learning_material_inventories.quantity) as total_quantity')
                    ->groupBy(DB::raw($materialLearningAreaExpr))
                    ->orderByDesc('total_quantity')
                    ->limit(10)
                    ->get()
                    ->map(fn ($row) => [
                        'learningArea' => $row->learning_area,
                        'totalMaterials' => (int) $row->total_materials,
                        'totalSubmissions' => (int) $row->total_submissions,
                        'totalQuantity' => (int) $row->total_quantity,
                    ])
                    ->values();

                $materialGradeLevelBreakdown = LearningMaterialInventory::query()
                    ->join('learning_materials', 'learning_materials.id', '=', 'learning_material_inventories.learning_material_id')
                    ->join('users', 'users.id', '=', 'learning_material_inventories.user_id');
                $applyMaterialInventoryFilters($materialGradeLevelBreakdown);

                $materialGradeLevelBreakdown = $materialGradeLevelBreakdown
                    ->selectRaw("{$materialGradeLevelExpr} as grade_level")
                    ->selectRaw('COUNT(DISTINCT learning_material_inventories.learning_material_id) as total_materials')
                    ->selectRaw('COUNT(learning_material_inventories.id) as total_submissions')
                    ->selectRaw('SUM(learning_material_inventories.quantity) as total_quantity')
                    ->groupBy(DB::raw($materialGradeLevelExpr))
                    ->orderByDesc('total_quantity')
                    ->limit(10)
                    ->get()
                    ->map(fn ($row) => [
                        'gradeLevel' => $row->grade_level,
                        'totalMaterials' => (int) $row->total_materials,
                        'totalSubmissions' => (int) $row->total_submissions,
                        'totalQuantity' => (int) $row->total_quantity,
                    ])
                    ->values();

                $materialMonthlyTrend = LearningMaterialInventory::query()
                    ->join('learning_materials', 'learning_materials.id', '=', 'learning_material_inventories.learning_material_id')
                    ->join('users', 'users.id', '=', 'learning_material_inventories.user_id');
                $applyMaterialInventoryFilters($materialMonthlyTrend);

                $materialMonthlyTrend = $materialMonthlyTrend
                    ->selectRaw("{$materialInventoryMonthExpr} as month_key")
                    ->selectRaw('COUNT(learning_material_inventories.id) as total_submissions')
                    ->selectRaw('COUNT(DISTINCT learning_material_inventories.user_id) as teachers_reported')
                    ->selectRaw('SUM(learning_material_inventories.quantity) as total_quantity')
                    ->groupBy(DB::raw($materialInventoryMonthExpr))
                    ->orderBy('month_key')
                    ->get()
                    ->map(fn ($row) => [
                        'monthKey' => (string) $row->month_key,
                        'label' => $this->monthKeyToLabel((string) $row->month_key),
                        'totalSubmissions' => (int) $row->total_submissions,
                        'teachersReported' => (int) $row->teachers_reported,
                        'totalQuantity' => (int) $row->total_quantity,
                    ])
                    ->values();

                $topMaterialsByQuantity = LearningMaterialInventory::query()
                    ->join('learning_materials', 'learning_materials.id', '=', 'learning_material_inventories.learning_material_id')
                    ->join('users', 'users.id', '=', 'learning_material_inventories.user_id');
                $applyMaterialInventoryFilters($topMaterialsByQuantity);

                $topMaterialsByQuantity = $topMaterialsByQuantity
                    ->select('learning_materials.id')
                    ->selectRaw("COALESCE(NULLIF(learning_materials.name, ''), 'Unknown Material') as material_name")
                    ->selectRaw("{$materialAuthorExpr} as author")
                    ->selectRaw("{$materialResourceTypeExpr} as resource_type")
                    ->selectRaw("{$materialLearningAreaExpr} as learning_area")
                    ->selectRaw("{$materialGradeLevelExpr} as grade_level")
                    ->selectRaw("{$materialPublisherExpr} as publisher")
                    ->selectRaw('COUNT(DISTINCT learning_material_inventories.user_id) as teacher_count')
                    ->selectRaw('COUNT(learning_material_inventories.id) as total_submissions')
                    ->selectRaw('SUM(learning_material_inventories.quantity) as total_quantity')
                    ->selectRaw('MAX(learning_material_inventories.updated_at) as updated_at')
                    ->groupBy(
                        'learning_materials.id',
                        DB::raw("COALESCE(NULLIF(learning_materials.name, ''), 'Unknown Material')"),
                        DB::raw($materialAuthorExpr),
                        DB::raw($materialResourceTypeExpr),
                        DB::raw($materialLearningAreaExpr),
                        DB::raw($materialGradeLevelExpr),
                        DB::raw($materialPublisherExpr),
                    )
                    ->orderByDesc('total_quantity')
                    ->orderByDesc('total_submissions')
                    ->limit(10)
                    ->get()
                    ->map(fn ($row) => [
                        'materialName' => $row->material_name,
                        'author' => $row->author,
                        'resourceType' => $row->resource_type,
                        'learningArea' => $row->learning_area,
                        'gradeLevel' => $row->grade_level,
                        'publisher' => $row->publisher,
                        'teacherCount' => (int) $row->teacher_count,
                        'totalSubmissions' => (int) $row->total_submissions,
                        'totalQuantity' => (int) $row->total_quantity,
                        'updatedAt' => $row->updated_at
                            ? Carbon::parse($row->updated_at)->toDateTimeString()
                            : null,
                    ])
                    ->values();

                $unreportedMaterialsQuery = LearningMaterial::query()
                    ->leftJoin('learning_material_inventories', 'learning_material_inventories.learning_material_id', '=', 'learning_materials.id')
                    ->leftJoin('users', function ($join) use (
                        $hasRoleColumn,
                        $hasDistrictColumn,
                        $hasSchoolColumn,
                        $hasDistrictFilter,
                        $hasSchoolFilter,
                        $filters
                    ) {
                        $join->on('users.id', '=', 'learning_material_inventories.user_id');

                        if ($hasRoleColumn) {
                            $join->where('users.role', 'teacher');
                        }

                        if ($hasDistrictColumn && $hasDistrictFilter) {
                            $join->where('users.district', $filters['district']);
                        }

                        if ($hasSchoolColumn && $hasSchoolFilter) {
                            $join->where('users.school_name', $filters['school']);
                        }
                    });

                $applyMaterialSearchOnMaterials($unreportedMaterialsQuery);

                $unreportedMaterials = $unreportedMaterialsQuery
                    ->select('learning_materials.id')
                    ->selectRaw("COALESCE(NULLIF(learning_materials.name, ''), 'Unknown Material') as material_name")
                    ->selectRaw("{$materialAuthorExpr} as author")
                    ->selectRaw("{$materialResourceTypeExpr} as resource_type")
                    ->selectRaw("{$materialLearningAreaExpr} as learning_area")
                    ->selectRaw("{$materialGradeLevelExpr} as grade_level")
                    ->selectRaw("{$materialPublisherExpr} as publisher")
                    ->selectRaw('COUNT(users.id) as teacher_reports')
                    ->groupBy(
                        'learning_materials.id',
                        DB::raw("COALESCE(NULLIF(learning_materials.name, ''), 'Unknown Material')"),
                        DB::raw($materialAuthorExpr),
                        DB::raw($materialResourceTypeExpr),
                        DB::raw($materialLearningAreaExpr),
                        DB::raw($materialGradeLevelExpr),
                        DB::raw($materialPublisherExpr),
                    )
                    ->havingRaw('COUNT(users.id) = 0')
                    ->orderBy('material_name')
                    ->limit(10)
                    ->get()
                    ->map(fn ($row) => [
                        'materialName' => $row->material_name,
                        'author' => $row->author,
                        'resourceType' => $row->resource_type,
                        'learningArea' => $row->learning_area,
                        'gradeLevel' => $row->grade_level,
                        'publisher' => $row->publisher,
                    ])
                    ->values();

                $materialAnalytics = [
                    'stats' => [
                        'totalMaterials' => (int) $materialTotalMaterials,
                        'totalSubmissions' => (int) $materialTotalSubmissions,
                        'totalQuantity' => (int) $materialTotalQuantity,
                        'teachersWithInventory' => (int) $materialTeachersWithInventory,
                        'schoolsWithInventory' => (int) $materialSchoolsWithInventory,
                        'districtsWithInventory' => (int) $materialDistrictsWithInventory,
                    ],
                    'teacherHoldings' => $materialTeacherHoldings,
                    'schoolHoldings' => $materialSchoolHoldings,
                    'resourceTypeBreakdown' => $materialResourceTypeBreakdown,
                    'learningAreaBreakdown' => $materialLearningAreaBreakdown,
                    'gradeLevelBreakdown' => $materialGradeLevelBreakdown,
                    'monthlyInventoryTrend' => $materialMonthlyTrend,
                    'topMaterialsByQuantity' => $topMaterialsByQuantity,
                    'unreportedMaterials' => $unreportedMaterials,
                ];
            }

            $userSelectColumns = ['id', 'name', 'email'];
            if ($hasDistrictColumn) {
                $userSelectColumns[] = 'district';
            }
            if ($hasSchoolColumn) {
                $userSelectColumns[] = 'school_name';
            }

            $recentActivityQuery = ResourceTracking::query()
                ->with([
                    'user:'.implode(',', $userSelectColumns),
                    'folder:id,name',
                    'file:id,title',
                ]);

            $recentActivityQuery->whereHas('user', function ($query) use (
                $hasRoleColumn,
                $hasDistrictColumn,
                $hasSchoolColumn,
                $hasDistrictFilter,
                $hasSchoolFilter,
                $filters,
                $hasSearchFilter,
                $searchLike
            ) {
                if ($hasRoleColumn) {
                    $query->where('role', 'teacher');
                }

                if ($hasDistrictColumn && $hasDistrictFilter) {
                    $query->where('district', $filters['district']);
                }

                if ($hasSchoolColumn && $hasSchoolFilter) {
                    $query->where('school_name', $filters['school']);
                }

                if ($hasSearchFilter) {
                    $query->where(function ($nested) use ($searchLike, $hasDistrictColumn, $hasSchoolColumn) {
                        $nested->where('name', 'like', $searchLike)
                            ->orWhere('email', 'like', $searchLike);

                        if ($hasDistrictColumn) {
                            $nested->orWhere('district', 'like', $searchLike);
                        }

                        if ($hasSchoolColumn) {
                            $nested->orWhere('school_name', 'like', $searchLike);
                        }
                    });
                }
            });

            if ($hasSearchFilter) {
                $recentActivityQuery->where(function ($query) use ($searchLike) {
                    $query->whereHas('folder', fn ($folderQuery) => $folderQuery->where('name', 'like', $searchLike))
                        ->orWhereHas('file', fn ($fileQuery) => $fileQuery->where('title', 'like', $searchLike))
                        ->orWhere('action', 'like', $searchLike);
                });
            }

            $recentActivity = $recentActivityQuery
                ->latest()
                ->limit(50)
                ->get()
                ->map(function (ResourceTracking $tracking) use ($hasDistrictColumn, $hasSchoolColumn) {
                    return [
                        'id' => $tracking->id,
                        'action' => $this->readableAction($tracking->action),
                        'district' => $hasDistrictColumn
                            ? ($tracking->user?->district ?: 'Unknown District')
                            : 'Unknown District',
                        'school_name' => $hasSchoolColumn
                            ? ($tracking->user?->school_name ?: 'Unknown School')
                            : 'Unknown School',
                        'user_name' => $tracking->user?->name ?: 'Unknown User',
                        'user_email' => $tracking->user?->email ?: 'N/A',
                        'target' => $tracking->folder?->name
                            ?? $tracking->file?->title
                            ?? 'N/A',
                        'time' => $tracking->created_at?->toDateTimeString(),
                    ];
                });

            $usersByRole = User::query()
                ->select(['role', 'is_admin'])
                ->get()
                ->map(function (User $user) use ($hasRoleColumn) {
                    $role = $hasRoleColumn ? strtolower(trim((string) $user->role)) : '';

                    if ($role === '') {
                        $role = $user->is_admin ? 'admin' : 'user';
                    }

                    return $role;
                })
                ->countBy()
                ->map(fn ($total, $role) => [
                    'role' => $role,
                    'total' => (int) $total,
                ])
                ->sortByDesc('total')
                ->values();

            return Inertia::render('Admin/Resources/Analytics', [
                'stats' => [
                    'total_downloads' => $totalDownloads,
                    'total_file_opens' => $totalFileOpens,
                    'total_folder_opens' => $totalFolderOpens,
                    'active_users' => $activeUsers,
                    'storage_used' => round($storageUsedBytes / (1024 * 1024), 2).' MB',
                ],
                'kpis' => [
                    'totalTeachers' => $totalTeachers,
                    'totalSchools' => $totalSchools,
                    'totalDistricts' => $totalDistricts,
                    'totalFolders' => $totalFolders,
                    'totalResources' => $totalResources,
                    'totalDownloads' => $totalDownloads,
                    'totalOpens' => $totalFileOpens,
                    'totalLockedResources' => $totalLockedResources,
                ],
                'teacherAnalytics' => [
                    'topActiveTeachers' => $topActiveTeachers,
                    'opensVsDownloads' => [
                        'opens' => $totalFileOpens,
                        'downloads' => $totalDownloads,
                    ],
                    'monthlyTrend' => $teacherMonthlyTrend,
                    'leastActiveTeachers' => $leastActiveTeachers,
                    'noActivityTeachers' => $noActivityTeachers,
                ],
                'districtAnalytics' => [
                    'mostActiveDistricts' => $districtPerformance->take(10)->values(),
                    'mostActiveSchools' => $schoolPerformance->take(10)->values(),
                    'leaderboard' => $schoolPerformance->take(25)->values(),
                    'heatmap' => $districtPerformance->take(12)->values(),
                ],
                'resourceAnalytics' => [
                    'topDownloadedResources' => $topDownloadedResources,
                    'topOpenedResources' => $topOpenedResources,
                    'categoryPopularity' => $categoryPopularity,
                    'monthlyUsageTrend' => $monthlyResourceUsage,
                    'neverOpenedResources' => $neverOpenedResources,
                    'lockedHighActivityResources' => $lockedHighActivityResources,
                ],
                'materialAnalytics' => $materialAnalytics,
                'filters' => $filters,
                'filterOptions' => $emptyAnalyticsPayload['filterOptions'],
                'districtStats' => $districtStats,
                'schoolStats' => $schoolStats,
                'topFolders' => $topFolders,
                'topFiles' => $topFiles,
                'recentActivity' => $recentActivity,
                'usersByRole' => $usersByRole,
                'loadError' => null,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to load analytics dashboard.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $emptyAnalyticsPayload['loadError'] = 'Analytics data could not be fully loaded. Please check tracking tables/migrations.';

            return Inertia::render('Admin/Resources/Analytics', $emptyAnalyticsPayload);
        }
    }

    private function allFoldersWithPath()
    {
        return Folder::with('parent')
            ->get()
            ->map(function (Folder $folder) {
                $pathParts = [$folder->name];
                $parent = $folder->parent;

                while ($parent) {
                    array_unshift($pathParts, $parent->name);
                    $parent = $parent->parent;
                }

                $folder->full_path = implode(' > ', $pathParts);

                return $folder;
            })
            ->sortBy('full_path')
            ->values();
    }

    private function depedEmailRules(): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            'regex:/@deped\.gov\.ph$/i',
        ];
    }

    private function applyUnlockWindow(Request $request, Folder|ResourceFile $resource, string $type): string
    {
        if ($request->boolean('clear')) {
            $resource->update([
                'unlock_starts_at' => null,
                'unlock_ends_at' => null,
            ]);

            return ucfirst($type).' unlock schedule cleared.';
        }

        $validated = $request->validate([
            'start_at' => ['required', 'date'],
            'duration_value' => ['required', 'integer', 'min:1', 'max:365'],
            'duration_unit' => ['required', Rule::in(['days', 'weeks', 'months'])],
        ]);

        $startAt = Carbon::parse($validated['start_at']);
        $durationValue = (int) $validated['duration_value'];
        $durationUnit = $validated['duration_unit'];

        $endAt = match ($durationUnit) {
            'days' => $startAt->copy()->addDays($durationValue),
            'weeks' => $startAt->copy()->addWeeks($durationValue),
            'months' => $startAt->copy()->addMonths($durationValue),
            default => $startAt->copy()->addDays($durationValue),
        };

        $resource->update([
            'is_locked' => true,
            'unlock_starts_at' => $startAt,
            'unlock_ends_at' => $endAt,
        ]);

        return ucfirst($type).' unlock schedule saved.';
    }

    private function readableAction(string $action): string
    {
        return match ($action) {
            'folder_opened' => 'Opened Folder',
            'opened_folder' => 'Opened Folder',
            'file_opened' => 'Opened File',
            'viewed_file' => 'Opened File',
            'file_downloaded' => 'Downloaded File',
            'downloaded_file' => 'Downloaded File',
            default => ucfirst(str_replace('_', ' ', $action)),
        };
    }

    private function sqlList(array $values): string
    {
        return implode(', ', array_map(
            fn (string $value) => "'".str_replace("'", "''", $value)."'",
            $values
        ));
    }

    private function monthBucketExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            'pgsql' => "TO_CHAR({$column}, 'YYYY-MM')",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    private function monthKeyToLabel(string $monthKey): string
    {
        $normalized = trim($monthKey);
        if ($normalized === '') {
            return 'Unknown';
        }

        try {
            return Carbon::createFromFormat('Y-m', $normalized)->format('M Y');
        } catch (Throwable) {
            return $normalized;
        }
    }

    private function uploadLimits(): array
    {
        $appFileMaxBytes = self::MAX_FILE_KB * 1024;

        return [
            'max_file_bytes' => $appFileMaxBytes,
            'max_file_label' => $this->formatBytes($appFileMaxBytes),
            'video_file_limit_label' => 'No app limit for video files',
            'php_upload_max_filesize' => (string) ini_get('upload_max_filesize'),
            'php_post_max_size' => (string) ini_get('post_max_size'),
        ];
    }

    private function isVideoResourceCategory(string $category): bool
    {
        return str_contains(strtolower($category), 'video');
    }

    private function normalizeIniSizeToBytes(string $size): int
    {
        $value = trim($size);
        if ($value === '') {
            return PHP_INT_MAX;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;
        if (! is_numeric($value)) {
            $number = (float) substr($value, 0, -1);
        }

        $multiplier = match ($unit) {
            'g' => 1024 * 1024 * 1024,
            'm' => 1024 * 1024,
            'k' => 1024,
            default => 1,
        };

        $bytes = (int) round($number * $multiplier);

        return $bytes > 0 ? $bytes : PHP_INT_MAX;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 1).' GB';
        }

        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}
