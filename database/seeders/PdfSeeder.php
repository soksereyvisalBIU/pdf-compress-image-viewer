<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pdf;

class PdfSeeder extends Seeder
{
    public function run()
    {
        Pdf::create([
            'name' => 'Sample PDF',
            'file_path' => 'pdfs/sample.pdf',
            'total_pages' => 50
        ]);

        // Add more sample PDFs as needed
    }
}