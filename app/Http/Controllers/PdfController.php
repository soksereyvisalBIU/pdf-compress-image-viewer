<?php

namespace App\Http\Controllers;

use App\Models\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PdfController extends Controller
{
    //

    public function index()
    {
        // $pdfs = Pdf::all();
        return view('pdfs.index');
    }

    // public function show($id)
    // {
    //     // $pdf = Pdf::findOrFail($id);
    //     $pdf = 1;
    //     return view('pdfs.show', compact('pdf'));
    // }



    public function show($pdfId, Request $request)
    {
        $perPage = 10;
        $page = $request->input('page', 1);
        $quality = $request->input('quality', 'medium'); // Quality preference

        // Retrieve the PDF record (assuming you have a Pdf model)
        // $pdf = Pdf::find($pdfId);



        // if (!$pdf) {
        //     abort(404, 'PDF not found.');
        // }

        $imagePath = 'public/pdfs/' . $pdfId; // Correct path to storage folder

        // Check if the directory exists
        if (!Storage::exists($imagePath)) {
            abort(404, 'Images not found.');
        }

        // Determine the quality of images to fetch
        $qualityPath = match ($quality) {
            'low' => 'low_quality',
            'medium' => 'medium_quality',
            default => 'high_quality',
        };

        // Get the files from the correct storage directory
        $allImages = Storage::files($imagePath . '/' . $qualityPath);

        // Paginate the images
        $images = array_slice($allImages, ($page - 1) * $perPage, $perPage);

        // Generate the correct public URLs for the images
        $images = array_map(fn($image) => Storage::url($image), $images);

        // Check if the request is expecting a JSON response (e.g., from fetch)
        if ($request->expectsJson()) {
            return response()->json([
                'images' => $images,
                'page' => $page,
                'hasMore' => count($allImages) > $page * $perPage, // Check if there are more images
            ]);
        }

        // For regular requests, return the full view
        return view('pdfs.show', compact('images', 'page', 'quality'));
    }

    // public function getPages($id, Request $request)
    // {
    //     $start = $request->input('start', 1);
    //     $count = $request->input('count', 20);

    //     $imagePath = 'public/pdfs/' . $id; // Correct path to storage folder

    //     // Check if the directory exists
    //     if (!Storage::exists($imagePath)) {
    //         abort(404, 'Images not found.');
    //     }

    //     // Get all files from the correct storage directory
    //     $allImages = Storage::files($imagePath);

    //     // Extract page numbers and sort images based on page number
    //     $imagesWithNumbers = [];
    //     foreach ($allImages as $image) {
    //         if (preg_match('/p_(\d+)\.webp$/', basename($image), $matches)) {
    //             $pageNumber = (int)$matches[1];
    //             $imagesWithNumbers[$pageNumber] = Storage::url($image);
    //         }
    //     }

    //     // Sort images by page number
    //     ksort($imagesWithNumbers);

    //     // Count total pages dynamically
    //     $totalPages = count($imagesWithNumbers);

    //     // Paginate the images
    //     $images = array_slice($imagesWithNumbers, ($start - 1), $count, true);

    //     // Generate the correct response format
    //     $pages = array_map(function ($url, $pageNumber) {
    //         return [
    //             'number' => $pageNumber,
    //             'url' => $url
    //         ];
    //     }, $images, array_keys($images));

    //     return response()->json([
    //         'pages' => $pages,
    //         'totalPages' => $totalPages
    //     ]);
    // }
    public function getPages($bookPDF, Request $request)
    {
        // Decode the encoded URL parameter
        $bookPDF = urldecode($bookPDF);
    
        // Receive start and count from the request
        $start = $request->input('start', 1);
        $count = $request->input('count', 20);
    
        // Transform the path: Replace "Book/PDF" with "Book/compressed" and remove ".pdf"
        $imagePath = str_replace(["Book/PDF", ".pdf"], ["Book/compressed", ""], $bookPDF);
    
        // Convert the imagePath to the correct public path
        $imagePath = public_path($imagePath);
    
        // Check if the directory exists
        if (!file_exists($imagePath)) {
            Log::error('Directory not found: ' . $imagePath);
            return response()->json(['error' => 'Images not found.'], 404);
        }
    
        // Get all files in the correct directory using glob()
        $allImages = glob($imagePath . '/p_*.webp');
    
        if (!$allImages) {
            Log::error('No images found in directory: ' . $imagePath);
            return response()->json(['error' => 'No images found.'], 404);
        }
    
        // Extract page numbers and sort images by page number
        $imagesWithNumbers = [];
        foreach ($allImages as $image) {
            if (preg_match('/p_(\d+)\.webp$/', basename($image), $matches)) {
                $pageNumber = (int) $matches[1];
    
                // Create the correct public URL for each image
                $publicUrlPath = str_replace(public_path(), '', $image);
                $publicUrlPath = str_replace('\\', '/', $publicUrlPath); // Ensure forward slashes
                $imagesWithNumbers[$pageNumber] = url($publicUrlPath);
            }
        }
    
        // Sort images by page number
        ksort($imagesWithNumbers);
    
        // Count total pages dynamically
        $totalPages = count($imagesWithNumbers);
    
        // Paginate the images based on start and count
        $images = array_slice($imagesWithNumbers, ($start - 1), $count, true);
    
        // Generate the correct response format
        $pages = array_map(function ($url, $pageNumber) {
            return [
                'number' => $pageNumber,
                'url' => $url
            ];
        }, $images, array_keys($images));
    
        // Return the response as JSON
        return response()->json([
            'pages' => $pages,
            'totalPages' => $totalPages,
        ]);
    }
    




    // public function getPages($id, Request $request)
    // {
    //     $start = $request->input('start', 1);
    //     $count = $request->input('count', 20);

    //     $imagePath = 'public/pdfs/' . $id; // Correct path to storage folder

    //     // Check if the directory exists
    //     if (!Storage::exists($imagePath)) {
    //         abort(404, 'Images not found.');
    //     }

    //     // Get the files from the correct storage directory
    //     $allImages = Storage::files($imagePath);

    //     // Extract page numbers and sort images based on page number
    //     $imagesWithNumbers = [];
    //     foreach ($allImages as $image) {
    //         if (preg_match('/p_(\d+)\.webp$/', basename($image), $matches)) {
    //             $pageNumber = (int)$matches[1];
    //             $imagesWithNumbers[$pageNumber] = Storage::url($image);
    //         }
    //     }

    //     // Sort images by page number
    //     ksort($imagesWithNumbers);

    //     // Paginate the images
    //     $images = array_slice($imagesWithNumbers, ($start - 1), $count, true);

    //     // Generate the correct response format
    //     $pages = array_map(function ($url, $pageNumber) {
    //         return [
    //             'number' => $pageNumber,
    //             'url' => $url
    //         ];
    //     }, $images, array_keys($images));

    //     // Assuming you have a way to get the total number of pages
    //     $totalPages = 100;

    //     return response()->json([
    //         'pages' => $pages,
    //         'totalPages' => $totalPages
    //     ]);
    // }



    // public function getPages($id, Request $request)
    // {
    //     // $pdf = Pdf::findOrFail($id);
    //     $start = $request->input('start', 1);
    //     $count = $request->input('count', 20);

    //     $imagePath = 'public/pdfs/' . $id; // Correct path to storage folder
    //     // $imagePath = 'storage/pdfs/' . $id; // Correct path to storage folder

    //     // Check if the directory exists
    //     if (!Storage::exists($imagePath)) {
    //         abort(404, 'Images not found.');
    //     }

    //     // Get the files from the correct storage directory
    //     $allImages = Storage::files($imagePath);

    //     // Paginate the images
    //     $images = array_slice($allImages, ($start - 1) * $count, $count);

    //     // Generate the correct public URLs for the images
    //     $images = array_map(fn($image) => Storage::url($image), $images);

    //     // $pages = [];
    //     // for ($i = $start; $i < $start + $count && $i <= 100; $i++) {
    //     //     $pages[] = [
    //     //         'number' => $i,
    //     //         'url' => asset("storage/pdfs/{$id}/p_{$i}.webp")
    //     //     ];
    //     // }

    //     return response()->json([
    //         'pages' => $images,
    //         'totalPages' => 100
    //     ]);
    // }
    // public function getPages($id, Request $request)
    // {
    //     // $pdf = Pdf::findOrFail($id);
    //     $start = $request->input('start', 1);
    //     $count = $request->input('count', 20);

    //     $pages = [];
    //     for ($i = $start; $i < $start + $count && $i <= 100; $i++) {
    //         $pages[] = [
    //             'number' => $i,
    //             'url' => asset("storage/pdfs/{$id}/p_{$i}.webp")
    //         ];
    //     }

    //     return response()->json([
    //         'pages' => $pages,
    //         'totalPages' => 100
    //     ]);
    // }

}
