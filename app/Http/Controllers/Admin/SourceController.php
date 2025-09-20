<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\EnqSource;

class SourceController extends Controller
{
    /**
     * List all sources.
     */
    public function index()
    {
        $sources = EnqSource::query()
            ->select([
                'enq_source_id as id',
                'enq_source_name as name',
                'enq_source_icon as icon',
                'enq_source_status as status',
            ])
            ->orderByDesc('enq_source_id')
            ->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Sources listed successfully.',
            'data'    => $sources,
        ], 200);
    }

    /**
     * Get a single source by ID.
     */
    public function edit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tbl_enq_source,enq_source_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $source = EnqSource::query()
            ->where('enq_source_id', $request->id)
            ->select([
                'enq_source_id as id',
                'enq_source_name as name',
                'enq_source_icon as icon',
                'enq_source_status as status',
            ])
            ->first();

        if (!$source) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Source not found.',
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Source details retrieved successfully.',
            'data'    => $source,
        ], 200);
    }

    /**
     * Create or update a source.
     */
    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'     => 'nullable|exists:tbl_enq_source,enq_source_id',
            'name'   => 'required|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'icon'   => 'nullable|string', // base64 image string
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $isUpdate = filled($request->id);

        $data = [
            'enq_source_name'   => $request->name,
            'enq_source_status' => $request->status ?? 'active',
            'updated_at'        => now(),
        ];

        // Handle Base64 Image Upload
          $image = $request->icon;
          if($image && preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
        try {
          

            if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
                $extension = strtolower($type[1]);

                if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                    return response()->json(['error' => 'Invalid image type.'], 400);
                }

                $image = substr($image, strpos($image, ',') + 1);
                $image = str_replace(' ', '+', $image);
                $imageData = base64_decode($image);

                if ($imageData === false) {
                    return response()->json(['error' => 'Base64 decode failed.'], 400);
                }

                $imageName = time() . '_' . uniqid() . '.' . $extension;
                $imagePath = 'uploads/source/icon/';
                $fullPath  = public_path($imagePath);

                if (!file_exists($fullPath)) {
                    mkdir($fullPath, 0755, true);
                }

                file_put_contents($fullPath . $imageName, $imageData);
                $data['enq_source_icon'] = $imagePath . $imageName;
            } else {
                return response()->json(['error' => 'Invalid image format.'], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Image upload failed. ' . $e->getMessage(),
            ], 500);
        }
    }

    $source = EnqSource::updateOrCreate(
        ['enq_source_id' => $request->id],
        $data
    );

        return response()->json([
            'status'  => 'success',
            'message' => $isUpdate ? 'Source updated successfully.' : 'Source created successfully.',
            'data'    => [
                'id'     => $source->enq_source_id,
                'name'   => $source->enq_source_name,
                'icon'   => $source->enq_source_icon,
                'status' => $source->enq_source_status,
            ],
        ], 200);
    }

    /**
     * Delete a source.
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tbl_enq_source,enq_source_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $source = EnqSource::where('enq_source_id', $request->id)->first();

        if (!$source) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Source not found.',
            ], 404);
        }

        $source->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Source deleted successfully.',
        ], 200);
    }

    /**
     * Update only status of a source.
     */
    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'     => 'required|exists:tbl_enq_source,enq_source_id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $source = EnqSource::where('enq_source_id', $request->id)->first();

        if (!$source) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Source not found.',
            ], 404);
        }

        $source->enq_source_status = $request->status;
        $source->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Source status updated successfully.',
            'data'    => [
                'id'     => $source->enq_source_id,
                'status' => $source->enq_source_status,
            ],
        ], 200);
    }
}
