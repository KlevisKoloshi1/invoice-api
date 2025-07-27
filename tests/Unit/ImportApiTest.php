<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\ImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\Import;

class ImportApiTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_example(): void
    {
        $this->assertTrue(true);
    }

    public function test_import_from_excel_creates_import_record()
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('test.xlsx');
        $service = app(ImportService::class);
        $import = $service->importFromExcel($file, 1);
        $this->assertInstanceOf(Import::class, $import);
        $this->assertEquals('completed', $import->status);
    }
}
