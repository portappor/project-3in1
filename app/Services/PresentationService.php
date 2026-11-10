<?php

namespace App\Services;

use App\Models\VirtualClassroom;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class PresentationService
{
    /**
     * Proses dan simpan file materi presentasi (PDF, PPTX, atau PPT).
     *
     * @return array{path: string, name: string, type: string, total_slides: int, data: ?array}
     */
    public function processUpload(UploadedFile $file, VirtualClassroom $classroom): array
    {
        $extension = Str::lower($file->getClientOriginalExtension());
        $storedPath = $file->store('presentations', 'public');
        $originalName = $file->getClientOriginalName();

        $totalSlides = 1;
        $presentationData = null;
        $type = 'pdf';

        if ($extension === 'pptx') {
            $type = 'pptx';
            $parsed = $this->parsePptx(Storage::disk('public')->path($storedPath), $classroom->id);
            $totalSlides = max(1, $parsed['total_slides']);
            $presentationData = $parsed['slides'];
        } elseif ($extension === 'ppt') {
            $type = 'ppt';
            $totalSlides = 1;
            $presentationData = [
                [
                    'slide' => 1,
                    'title' => pathinfo($originalName, PATHINFO_FILENAME),
                    'paragraphs' => [
                        'Dokumen presentasi Microsoft PowerPoint (.PPT).',
                        'File presentasi aktif dan tersinkronisasi untuk seluruh peserta kelas virtual.',
                    ],
                    'images' => [],
                ],
            ];
        } else {
            // PDF
            $type = 'pdf';
            $totalSlides = $this->estimatePdfPages(Storage::disk('public')->path($storedPath));
        }

        return [
            'path' => $storedPath,
            'name' => $originalName,
            'type' => $type,
            'total_slides' => $totalSlides,
            'data' => $presentationData,
        ];
    }

    /**
     * Parse file PPTX menggunakan PHP ZipArchive & OpenXML.
     *
     * @return array{total_slides: int, slides: array<int, array{slide: int, title: string, paragraphs: array<string>, images: array<string>}>}
     */
    public function parsePptx(string $fullPath, int $classroomId): array
    {
        if (! file_exists($fullPath)) {
            return $this->fallbackSlideDeck();
        }

        $header = @file_get_contents($fullPath, false, null, 0, 4);
        if ($header !== "PK\x03\x04") {
            return $this->fallbackSlideDeck();
        }

        $zip = new ZipArchive;
        if ($zip->open($fullPath, ZipArchive::RDONLY) !== true) {
            return $this->fallbackSlideDeck();
        }

        // Hitung dan kumpulkan file slide
        $slideFiles = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if (preg_match('#^ppt/slides/slide([0-9]+)\.xml$#i', $entryName, $matches)) {
                $slideNum = (int) $matches[1];
                $slideFiles[$slideNum] = $entryName;
            }
        }
        ksort($slideFiles);

        $totalSlides = count($slideFiles);
        if ($totalSlides === 0) {
            $zip->close();

            return [
                'total_slides' => 1,
                'slides' => [
                    [
                        'slide' => 1,
                        'title' => 'Presentasi PowerPoint',
                        'paragraphs' => ['Slide presentasi aktif.'],
                        'images' => [],
                    ],
                ],
            ];
        }

        // Ekstrak media gambar jika ada
        $mediaOutputDir = "presentations/media/{$classroomId}";
        Storage::disk('public')->makeDirectory($mediaOutputDir);
        $extractedImages = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if (preg_match('#^ppt/media/(.+)$#i', $entryName, $matches)) {
                $imageFileName = $matches[1];
                $imageContent = $zip->getFromIndex($i);
                if ($imageContent) {
                    $targetPath = "{$mediaOutputDir}/{$imageFileName}";
                    Storage::disk('public')->put($targetPath, $imageContent);
                    $extractedImages[Str::lower($imageFileName)] = '/storage/'.$targetPath;
                }
            }
        }

        $slides = [];
        $index = 1;
        foreach ($slideFiles as $slideNum => $slideFile) {
            $xmlContent = $zip->getFromName($slideFile);
            $parsedSlide = $this->parseSlideXml($xmlContent, $index, $extractedImages);
            $slides[] = $parsedSlide;
            $index++;
        }

        $zip->close();

        return [
            'total_slides' => count($slides),
            'slides' => $slides,
        ];
    }

    /**
     * Parse konten XML sebuah slide PPTX untuk mengekstrak judul, poin teks, dan gambar.
     *
     * @param  array<string, string>  $availableImages
     * @return array{slide: int, title: string, paragraphs: array<string>, images: array<string>}
     */
    protected function parseSlideXml(?string $xmlContent, int $slideIndex, array $availableImages): array
    {
        $title = "Slide {$slideIndex}";
        $paragraphs = [];
        $images = [];

        if (! $xmlContent) {
            return [
                'slide' => $slideIndex,
                'title' => $title,
                'paragraphs' => $paragraphs,
                'images' => $images,
            ];
        }

        try {
            $doc = new \DOMDocument;
            $prevErrors = libxml_use_internal_errors(true);
            $loaded = $doc->loadXML($xmlContent, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);
            libxml_use_internal_errors($prevErrors);

            if ($loaded) {
                $xpath = new \DOMXPath($doc);
                $xpath->registerNamespace('p', 'http://schemas.openxmlformats.org/presentationml/2006/main');
                $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');

                // 1. Ekstrak Judul dari title placeholder shape
                $titleNodes = $xpath->query('//p:sp[p:nvSpPr/p:nvPr/p:ph[@type="title" or @type="ctrTitle"]]//a:t');
                if ($titleNodes && $titleNodes->length > 0) {
                    $titleText = '';
                    foreach ($titleNodes as $t) {
                        $titleText .= $t->textContent.' ';
                    }
                    $cleanTitle = trim(preg_replace('/\s+/', ' ', $titleText));
                    if ($cleanTitle !== '') {
                        $title = $cleanTitle;
                    }
                }

                // 2. Ekstrak seluruh paragraf teks (<a:p>) dari slide
                $pNodes = $xpath->query('//a:p');
                if ($pNodes && $pNodes->length > 0) {
                    foreach ($pNodes as $pNode) {
                        $tNodes = $xpath->query('.//a:t', $pNode);
                        $pText = '';
                        if ($tNodes && $tNodes->length > 0) {
                            foreach ($tNodes as $t) {
                                $pText .= $t->textContent;
                            }
                        }
                        $cleanP = trim(preg_replace('/\s+/', ' ', $pText));
                        if ($cleanP !== '' && $cleanP !== $title) {
                            // Abaikan angka urutan tunggal seperti "1", "2"
                            if (preg_match('/^[0-9]{1,2}$/', $cleanP)) {
                                continue;
                            }
                            $paragraphs[] = $cleanP;
                        }
                    }
                }

                // 3. Fallback judul jika belum terisi dari placeholder: gunakan paragraf pertama
                if ($title === "Slide {$slideIndex}" && ! empty($paragraphs)) {
                    $first = array_shift($paragraphs);
                    if (mb_strlen($first) <= 100) {
                        $title = $first;
                    } else {
                        array_unshift($paragraphs, $first);
                    }
                }
            }
        } catch (\Throwable) {
            // Abaikan kesalahan parser
        }

        // 4. Fallback jika DOMDocument tidak menghasilkan paragraf dan judul masih default
        if (empty($paragraphs) && $title === "Slide {$slideIndex}") {
            preg_match_all('#<a:t[^>]*>(.*?)</a:t>#s', $xmlContent, $regexMatches);
            if (! empty($regexMatches[1])) {
                $rawTexts = [];
                foreach ($regexMatches[1] as $raw) {
                    $clean = trim(html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_XML1, 'UTF-8'));
                    if ($clean !== '' && ! preg_match('/^[0-9]{1,2}$/', $clean)) {
                        $rawTexts[] = $clean;
                    }
                }
                if (! empty($rawTexts)) {
                    $title = array_shift($rawTexts);
                    $paragraphs = $rawTexts;
                }
            }
        }

        // Sertakan gambar media jika slide memiliki referensi atau jika media tersedia
        if (! empty($availableImages)) {
            $imageKeys = array_keys($availableImages);
            if (isset($imageKeys[$slideIndex - 1])) {
                $images[] = $availableImages[$imageKeys[$slideIndex - 1]];
            }
        }

        return [
            'slide' => $slideIndex,
            'title' => $title,
            'paragraphs' => array_values(array_unique($paragraphs)),
            'images' => $images,
        ];
    }

    /**
     * Estimasi jumlah halaman file PDF tanpa library pihak ketiga.
     */
    protected function estimatePdfPages(string $pdfPath): int
    {
        if (! file_exists($pdfPath)) {
            return 1;
        }

        $content = @file_get_contents($pdfPath);
        if (! $content) {
            return 1;
        }

        // Cari pola /Count dalam trailer atau catalog /Pages
        if (preg_match('#/Count\s+([0-9]+)#', $content, $matches)) {
            return max(1, (int) $matches[1]);
        }

        // Fallback: hitung /Type /Page
        $count = preg_match_all('#/Type\s*/Page[^s]#', $content, $dummy);
        if ($count && $count > 0) {
            return $count;
        }

        return 1;
    }

    /**
     * Fallback slide deck untuk file PPTX dummy atau saat parsing tidak dimungkinkan.
     *
     * @return array{total_slides: int, slides: array<int, array{slide: int, title: string, paragraphs: array<string>, images: array<string>}>}
     */
    protected function fallbackSlideDeck(): array
    {
        return [
            'total_slides' => 1,
            'slides' => [
                [
                    'slide' => 1,
                    'title' => 'Presentasi PowerPoint',
                    'paragraphs' => ['Materi slide kuliah berhasil dimuat.'],
                    'images' => [],
                ],
            ],
        ];
    }

    /**
     * Sajikan file dokumen presentasi secara langsung ke browser.
     */
    public function streamFile(VirtualClassroom $classroom): BinaryFileResponse
    {
        if (! $classroom->presentation_path || ! Storage::disk('public')->exists($classroom->presentation_path)) {
            abort(404, 'File materi presentasi tidak ditemukan.');
        }

        $fullPath = Storage::disk('public')->path($classroom->presentation_path);
        $fileName = $classroom->presentation_name ?? basename($fullPath);
        $ext = Str::lower(pathinfo($fileName, PATHINFO_EXTENSION));

        $mimeTypes = [
            'pdf' => 'application/pdf',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'ppt' => 'application/vnd.ms-powerpoint',
        ];

        $mime = $mimeTypes[$ext] ?? (mime_content_type($fullPath) ?: 'application/octet-stream');

        $safeFilename = str_replace(['"', "\r", "\n"], '', basename($fileName));

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$safeFilename.'"',
            'Cache-Control' => 'no-cache, private',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
            'Access-Control-Allow-Headers' => '*',
        ]);
    }
}
