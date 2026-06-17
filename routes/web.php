<?php

use App\Ai\Agents\CandidateInfoExtractor;
use App\Http\Controllers\OfferController;
use App\Services\AiClient;
use App\Services\Extraction\LlamaParseService;
use App\Services\Extraction\PdfExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    if (app()->isLocal()) {
        Route::get('info', function () {
            return view('debug-info');
        })->name('debug.info');

        Route::post('info', function (Request $request) {
            $request->validate(['cv' => 'required|file|mimes:pdf|max:10240']);

            $path = $request->file('cv')->storeAs('debug', time().'_'.$request->file('cv')->getClientOriginalName());
            $output = [];

            // Step 1: LlamaParse
            $llamaParse = app(LlamaParseService::class);
            if ($llamaParse->isAvailable()) {
                $result = $llamaParse->parsePdf($path);
                $output['llama_status'] = $result['status'];
                if ($result['status'] === 'success') {
                    $output['llama_data'] = $result['data'];
                    $output['raw_keys'] = $result['data']['raw_keys'] ?? [];
                    $extractedText = $result['data']['extracted_text'] ?? '';
                    $output['extracted_text'] = $extractedText;
                } else {
                    $output['llama_error'] = $result['error'] ?? 'Unknown error';
                    $fullPath = Storage::path($path);
                    $extractor = new PdfExtractor;
                    $extractedText = $extractor->extract($fullPath);
                    $output['fallback_used'] = true;
                }
            } else {
                $output['llama_status'] = 'unavailable';
                $fullPath = Storage::path($path);
                $extractor = new PdfExtractor;
                $extractedText = $extractor->extract($fullPath);
                $output['fallback_used'] = true;
            }

            $output['extracted_text_length'] = strlen($extractedText ?? '');
            $output['extracted_text'] = $extractedText ?? '';

            // Step 2: Groq
            if (! empty($extractedText) && strlen($extractedText) >= 50) {
                try {
                    $agent = new CandidateInfoExtractor;
                    $response = app(AiClient::class)->prompt($agent, $extractedText);
                    $output['groq_raw'] = $response;
                    $output['groq_parsed'] = json_decode($response, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        // Try extracting from markdown code block
                        if (preg_match('/```json\s*(.*?)\s*```/s', $response, $m)) {
                            $output['groq_parsed'] = json_decode($m[1], true);
                            $output['groq_parse_method'] = 'markdown_extract';
                        } else {
                            $output['groq_parse_error'] = json_last_error_msg();
                        }
                    } else {
                        $output['groq_parse_method'] = 'direct';
                    }
                } catch (Exception $e) {
                    $output['groq_error'] = get_class($e).': '.$e->getMessage();
                }
            } else {
                $output['groq_skipped'] = 'Text too short or empty';
            }

            return view('debug-info', ['result' => $output]);
        });
    }
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Volt::route('offers', 'livewire.offers.index')->name('offers.index');
    Volt::route('offers/create', 'livewire.offers.create')->name('offers.create');
    Volt::route('offers/archived', 'livewire.offers.archived')->name('offers.archived');
    Volt::route('offers/{offer}', 'livewire.offers.show')->name('offers.show');
    Volt::route('offers/{offer}/edit', 'livewire.offers.edit')->name('offers.edit');
    Volt::route('offers/{offer}/submit', 'livewire.offers.submit')->name('offers.submit');

    Route::delete('offers/{offer}', [OfferController::class, 'destroy'])->name('offers.destroy');
    Route::post('offers/{offer}/restore', [OfferController::class, 'restore'])->name('offers.restore')->withTrashed();
    Route::delete('offers/{offer}/force', [OfferController::class, 'forceDelete'])->name('offers.forceDelete')->withTrashed();
});

require __DIR__.'/settings.php';
