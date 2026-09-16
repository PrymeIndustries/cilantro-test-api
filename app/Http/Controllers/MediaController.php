<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\AuditLog;
use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\Facades\Image;
use Org_Heigl\Ghostscript\Ghostscript;
use Spatie\PdfToImage\Pdf;
use Illuminate\Support\Facades\DB;

class MediaController extends Controller
{
    public function index(): Response
    {
        $files = Media::whereNull('category')->get();
        return Inertia::render('Media/Index', [
            'files' => $files,
            'directories' => $this->get_media_categories()['data']
        ]);
    }

    public function dir_content(string $dir): array
    {
        return [
            'status' => 'success',
            'files' => Media::where('category', $dir)->get()
        ];
    }

    public function get_media_categories(): array
    {
        return [
            'data' => Media::whereNotNull('category')->select('category')->distinct()->get()->pluck('category')
        ];
    }

    public function store(Request $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $request->validate([
                'files' => 'required|array',
            ]);
            $files = $request->file('files');
            $saved_files = [];
            $category = $request->input('category');
            foreach ($files as $file) {
                $file_ext = $file->extension();
                $path = Storage::disk('public')->putFileAs("/media/$category",
                    new File($file), pathinfo($file->hashName(), PATHINFO_FILENAME) . time() . '.' . $file_ext);
                $file_type = str_starts_with($file->getMimeType(), 'application') ? 'document' : explode('/', $file->getMimeType())[0];

                // Optimize thumbnail creation and saving (merge)
                $thumbnailPath = storage_path('app/public/thumbnail/'); // Replaced double backslashes with forward slash
                $thumbnailName = time() . pathinfo($file->hashName(), PATHINFO_FILENAME) . '.jpg';
                $file_name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                $media = new Media();
                if (!file_exists($thumbnailPath)) {
                    mkdir($thumbnailPath, 666, true);
                }
                if ($file_type == 'image') {
                    $image = Image::make($file);
                    $image->resize(320, 240, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                    $image->save($thumbnailPath . $thumbnailName, 30);

                    $media->thumbnail = "thumbnail/$thumbnailName";

                } elseif ($file_type == 'document') {

                    if (str_contains($file_ext, 'pdf')) {
                        try {
                            $gs = new Ghostscript();
                            Ghostscript::setGsPath(env('GS_PATH'));
                        } catch (\Exception $e) {
                        }
                        $pdf = new Pdf(storage_path('app/public/' . $path));
                        $pdf->setResolution(300)->saveImage($thumbnailPath . $thumbnailName);
                        $img_tbn = Image::make(storage_path('app/public/') . "thumbnail/$thumbnailName");
                        $img_tbn->save($img_tbn->dirname . '/' . $img_tbn->basename, 30);

                        $media->thumbnail = "thumbnail/$thumbnailName";
                    }
                } elseif ($file_type == 'video') {
                    // get video length and process it
                    // assign the value to time_to_image (which will get screenshot of video at that specified seconds)
                    // (new \Pawlox\VideoThumbnail\VideoThumbnail)->createThumbnail(storage_path('app/public/' . $path), $thumbnailPath, $thumbnailName, 2, 1920, 1080);

                    $media->thumbnail = "thumbnail/$thumbnailName";
                } elseif ($file_type == 'audio') {
                }

                $media->category = $category;
                $media->user_id = auth()->id();
                $media->path = $path;
                $media->type = $file_type;
                /** @todo use file label when uploading multiple files. */
                $media->label = $request->input('label') ?: $file_name;
                $media->description = $request->input('description') ?: $file_name;

                if ($media->save()) {
                    $active_ay = AcademicYear::where('active', true)->first();
                    AuditLog::create([
                        'user_id' => auth()->id() ?? 0,
                        'action' => 'UPLOAD_MEDIA',
                        'auditable_type' => Media::class,
                        'auditable_id' => $media->id,
                        'description' => 'Media ' . $media->label . ' uploaded to ' . $media->category,
                        'old_values' => null,
                        'new_values' => json_encode($media),
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $active_ay ? $active_ay->id : 0
                    ]);

                    $saved_files[] = $media->refresh();
                }
            }

            return response()->json(['status' => 'success', 'data' => $saved_files], 201);
        });
        
    }


    public function update(Media $media, Request $request): JsonResponse
    {
        return DB::transaction(function () use ($media, $request) {
            $this->validate($request, [
                'label' => 'required'
            ]);

            $old_media = $media->replicate();
            $media->label = $request->input('label');
            if ($media->update()) {
                $active_ay = AcademicYear::where('active', true)->first();
                AuditLog::create([
                    'user_id' => auth()->id() ?? 0,
                    'action' => 'UPDATE_MEDIA',
                    'auditable_type' => Media::class,
                    'auditable_id' => $media->id,
                    'description' => 'Media label updated to ' . $media->label,
                    'old_values' => json_encode($old_media),
                    'new_values' => json_encode($media),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $active_ay ? $active_ay->id : 0
                ]);

                return response()->json([
                    'status' => 'success',
                    'data' => $media
                ]);
            }
        });
        
    }


    public function delete(Media $media): JsonResponse
    {
        return DB::transaction(function () use ($media) {
            if (Storage::exists('public/' . $media->path)) {
                Storage::delete('public/' . $media->path);
                if (Storage::exists('public/' . $media->thumbnail)) {
                    Storage::delete('public/' . $media->thumbnail);
                }
            }

            if ($media->delete()) {
                $active_ay = AcademicYear::where('active', true)->first();
                AuditLog::create([
                    'user_id' => auth()->id() ?? 0,
                    'action' => 'DELETE_MEDIA',
                    'auditable_type' => Media::class,
                    'auditable_id' => $media->id,
                    'description' => 'Media ' . $media->label . ' deleted from ' . $media->category,
                    'old_values' => json_encode($media),
                    'new_values' => null,
                    'ip_address' => request()->ip(),
                    'academic_year_id' => $active_ay ? $active_ay->id : 0
                ]);

                return response()->json([
                    'status' => 'success'
                ]);
            }
        });
        
    }


    public function fetch_media_list($type = ''): JsonResponse
    {
        return response()->json(Media::where(function (Builder $q) use ($type) {
            return $type ? $q->whereType($type) : null;
        })->orderBy('category')->get());
    }


    public function fetch_media_list_for_editor($type = ''): JsonResponse
    {
        $data = [];
        array_map(function ($media) use (&$data) {
            if (!isset($data[$media['category']])) {
                $data[$media['category']] = [
                    'id' => $media['id'],
                    'title' => $media['category'],
                    'menu' => []
                ];
            }
            $data[$media['category']]['menu'][] = [
                'id' => $media['id'], 'title' => $media['label'], 'value' => url(Storage::url($media['path'])), 'type' => $media['type']
            ];
        }, Media::whereType($type)->get()->toArray());
        return response()->json($data);
    }



}
