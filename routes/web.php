<?php

use App\Ai\Agents\CandidateInfoExtractor;
use App\Http\Controllers\OfferController;
use App\Services\AiClient;
use App\Services\Extraction\ExtractionOrchestrator;
use App\Services\Extraction\LlamaParseService;
use App\Services\Extraction\LocalPdfExtractor;
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

            if ($path === false) {
                return response()->json(['error' => 'Failed to store uploaded file'], 500);
            }

            $output = [];

            // Step 0: Orchestrator chain (Docling → LlamaParse → LocalPdfExtractor)
            $orchestrator = app(ExtractionOrchestrator::class);
            $result = $orchestrator->extract($path);

            $output['orchestrator_extractor'] = $result->extractorName;
            $output['orchestrator_status'] = $result->status;
            $output['orchestrator_error'] = $result->errorMessage;

            if ($result->isCompleted()) {
                $extractedText = $result->content;
            } elseif ($result->isPending()) {
                $extractedText = '';
                $output['orchestrator_pending'] = true;
            } else {
                $extractedText = '';
            }

            // Step 1: LlamaParse (direct test)
            $llamaParse = app(LlamaParseService::class);
            $output['llama_available'] = $llamaParse->isAvailable();
            if ($llamaParse->isAvailable()) {
                $llamaResult = $llamaParse->parsePdf($path);
                $output['llama_status'] = $llamaResult['status'];
                if ($llamaResult['status'] === 'success') {
                    $output['llama_data'] = $llamaResult['data'] ?? [];
                    $output['raw_keys'] = ($llamaResult['data'] ?? [])['raw_keys'] ?? [];
                } else {
                    $output['llama_error'] = $llamaResult['error'] ?? 'Unknown error';
                }
            } else {
                $output['llama_status'] = 'unavailable';
            }

            // Step 2: LocalPdfExtractor (direct test)
            $local = new LocalPdfExtractor;
            $localResult = $local->extract(Storage::path($path));
            $output['local_extractor_status'] = $localResult->status;
            $output['local_extractor_length'] = strlen($localResult->content);
            $output['local_extractor_truncated'] = mb_substr($localResult->content, 0, 500);

            // Fallback text for Groq
            if (empty($extractedText)) {
                $extractedText = $localResult->content;
            }

            $output['extracted_text_length'] = strlen($extractedText);
            $output['extracted_text'] = $extractedText;

            // Step 3: Groq
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
