<x-layouts.app>
        <div class="p-6">
            <div class="mb-8">
                <flux:heading size="lg">Debug: CV Extraction Info</flux:heading>
                <flux:text class="mt-1 text-zinc-500">Upload a PDF to see LlamaParse and Groq API responses.</flux:text>
            </div>

            @if (! isset($result))
                <form method="POST" action="{{ route('debug.info') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    <flux:card>
                        <div class="space-y-4">
                            <div>
                                <flux:input name="cv" type="file" label="Select PDF" accept=".pdf" />
                                @error('cv')
                                    <flux:error>{{ $message }}</flux:error>
                                @enderror
                            </div>
                            <flux:button type="submit" variant="primary">Upload & Debug</flux:button>
                        </div>
                    </flux:card>
                </form>
            @else
                <div class="space-y-6">
                    <flux:card>
                        <flux:heading size="sm" class="mb-3">Extraction Result</flux:heading>
                        <div class="space-y-2 text-sm">
                            <div><span class="font-medium">Extractor:</span>
                                @php $ext = $result['orchestrator_extractor'] ?? 'N/A'; @endphp
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium
                                    {{ match($ext) { 'DoclingExtractor' => 'bg-sky-100 text-sky-700', 'LlamaParseExtractor' => 'bg-purple-100 text-purple-700', 'LocalPdfExtractor' => 'bg-amber-100 text-amber-700', default => 'bg-zinc-100 text-zinc-700' } }}">
                                    @switch($ext)
                                        @case('DoclingExtractor') @break
                                        @case('LlamaParseExtractor') @break
                                        @case('LocalPdfExtractor') @break
                                    @endswitch
                                    {{ $ext }}
                                </span>
                            </div>
                            <div><span class="font-medium">Status:</span> {{ $result['orchestrator_status'] ?? 'N/A' }}</div>
                            @if (isset($result['orchestrator_error']))
                                <div class="text-red-600">Error: {{ $result['orchestrator_error'] }}</div>
                            @endif
                            @if (isset($result['orchestrator_pending']))
                                <div class="text-amber-600">Pending — job released back to queue.</div>
                            @endif
                            <div><span class="font-medium">Length:</span> {{ $result['extracted_text_length'] }} characters</div>
                        </div>
                        @if (($result['extracted_text_length'] ?? 0) < 50)
                            <div class="mt-2 rounded-lg bg-amber-50 p-3 text-amber-700 text-sm">
                                Text too short — may be a scanned image.
                            </div>
                        @endif
                    </flux:card>

                    <flux:card>
                        <flux:heading size="sm" class="mb-3">Extractors Comparison</flux:heading>
                        <div class="space-y-3">
                            <div class="rounded-lg border p-3">
                                <div class="font-medium text-sm">DoclingExtractor</div>
                                <div class="text-xs text-zinc-500">Status: {{ $result['orchestrator_status'] ?? 'N/A' }}</div>
                                @if ($result['orchestrator_extractor'] === 'DoclingExtractor' && ($result['orchestrator_status'] ?? '') === 'completed')
                                    <div class="text-xs text-green-600">Selected by orchestrator</div>
                                @endif
                            </div>
                            <div class="rounded-lg border p-3">
                                <div class="font-medium text-sm">LlamaParse</div>
                                <div class="text-xs text-zinc-500">Available: {{ isset($result['llama_available']) ? ($result['llama_available'] ? 'Yes' : 'No') : 'N/A' }}</div>
                                @if (isset($result['llama_status']))
                                    <div class="text-xs text-zinc-500">Status: {{ $result['llama_status'] }}</div>
                                @endif
                                @if (isset($result['llama_error']))
                                    <div class="text-xs text-red-600">{{ $result['llama_error'] }}</div>
                                @endif
                                @if (isset($result['raw_keys']))
                                    <div class="text-xs text-zinc-400">Keys: {{ implode(', ', $result['raw_keys']) }}</div>
                                @endif
                            </div>
                            <div class="rounded-lg border p-3">
                                <div class="font-medium text-sm">LocalPdfExtractor</div>
                                <div class="text-xs text-zinc-500">Status: {{ $result['local_extractor_status'] ?? 'N/A' }}</div>
                                <div class="text-xs text-zinc-500">Length: {{ $result['local_extractor_length'] ?? 0 }} chars</div>
                                @if (($result['local_extractor_truncated'] ?? '') !== '')
                                    <pre class="mt-1 bg-zinc-50 dark:bg-zinc-800 rounded p-2 text-xs max-h-24 overflow-y-auto">{{ $result['local_extractor_truncated'] }}</pre>
                                @endif
                            </div>
                        </div>
                    </flux:card>

                    <flux:card>
                        <flux:heading size="sm" class="mb-3">Extracted Text (used for AI)</flux:heading>
                        <pre class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 text-xs overflow-x-auto max-h-64 overflow-y-auto">{{ $result['extracted_text'] ?? 'N/A' }}</pre>
                    </flux:card>

                    <flux:card>
                        <flux:heading size="sm" class="mb-3">Groq AI Response</flux:heading>
                        @if (isset($result['groq_error']))
                            <div class="rounded-lg bg-red-50 p-3 text-red-700 text-sm mb-2">Error: {{ $result['groq_error'] }}</div>
                        @endif
                        @if (isset($result['groq_skipped']))
                            <flux:text class="text-amber-600">{{ $result['groq_skipped'] }}</flux:text>
                        @endif
                        @if (isset($result['groq_parse_method']))
                            <flux:text class="mb-2 text-sm text-zinc-500">Parse method: {{ $result['groq_parse_method'] }}</flux:text>
                        @endif
                        @if (isset($result['groq_raw']))
                            <details open>
                                <summary class="cursor-pointer text-sm font-medium text-zinc-600 mb-2">Raw Groq Response</summary>
                                <pre class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 text-xs overflow-x-auto max-h-48 overflow-y-auto mt-2">{{ $result['groq_raw'] }}</pre>
                            </details>
                        @endif
                        @if (isset($result['groq_parsed']))
                            <details open>
                                <summary class="cursor-pointer text-sm font-medium text-zinc-600 mb-2 mt-3">Parsed JSON</summary>
                                <pre class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 text-xs overflow-x-auto max-h-96 overflow-y-auto mt-2">{{ json_encode($result['groq_parsed'], JSON_PRETTY_PRINT) }}</pre>
                            </details>
                        @endif
                    </flux:card>

                    <flux:card>
                        <flux:heading size="sm" class="mb-3">Analyse Against Offer</flux:heading>
                        <form method="POST" action="{{ route('debug.info.analyse') }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="extracted_text" value="{{ $result['extracted_text'] ?? '' }}">
                            <div>
                                <flux:select name="offer_id" label="Select Offer">
                                    @forelse ($offers as $offer)
                                        <option value="{{ $offer->id }}">{{ $offer->title }}</option>
                                    @empty
                                        <option value="">No offers available</option>
                                    @endforelse
                                </flux:select>
                            </div>
                            <flux:button type="submit" variant="primary">Analyse</flux:button>
                        </form>

                        @if (isset($analysis_result))
                            <div class="mt-4 space-y-3">
                                <flux:heading size="sm" class="mb-1">Analysis Result — {{ $analysis_result['offer_title'] }}</flux:heading>

                                @if ($analysis_result['parsed'] && ! isset($analysis_result['parsed']['error']))
                                    <div class="flex items-center gap-3">
                                        <flux:heading size="lg">{{ $analysis_result['parsed']['matching_score'] ?? 'N/A' }}%</flux:heading>
                                        <flux:badge size="sm" class="
                                            @php $rec = $analysis_result['parsed']['recommendation'] ?? ''; @endphp
                                            {{ $rec === 'shortlisted' ? 'bg-green-100 text-green-700' : ($rec === 'on_hold' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}
                                        ">{{ ucfirst($rec ?: 'N/A') }}</flux:badge>
                                    </div>
                                    <div><span class="font-medium">Strengths:</span> {{ $analysis_result['parsed']['strengths'] ?? 'N/A' }}</div>
                                    <div><span class="font-medium">Gaps:</span> {{ $analysis_result['parsed']['gaps'] ?? 'N/A' }}</div>
                                    <div><span class="font-medium">Justification:</span> {{ $analysis_result['parsed']['justification'] ?? 'N/A' }}</div>
                                    @if (count($analysis_result['parsed']['extracted_skills'] ?? []))
                                        <div>
                                            <span class="font-medium">Extracted Skills:</span>
                                            @foreach ($analysis_result['parsed']['extracted_skills'] as $skill)
                                                <flux:badge color="indigo" size="sm">{{ $skill }}</flux:badge>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if (count($analysis_result['parsed']['missing_skills'] ?? []))
                                        <div>
                                            <span class="font-medium">Missing Skills:</span>
                                            @foreach ($analysis_result['parsed']['missing_skills'] as $skill)
                                                <flux:badge color="red" size="sm">{{ $skill }}</flux:badge>
                                            @endforeach
                                        </div>
                                    @endif
                                @else
                                    <div class="rounded-lg bg-red-50 p-3 text-red-700 text-sm">Failed to parse AI response.</div>
                                @endif

                                <details @if (! $analysis_result['parsed']) open @endif>
                                    <summary class="cursor-pointer text-sm font-medium text-zinc-600">Raw AI Response</summary>
                                    <pre class="mt-2 bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 text-xs max-h-48 overflow-y-auto">{{ $analysis_result['raw'] }}</pre>
                                </details>
                            </div>
                        @endif
                    </flux:card>

                    <flux:card>
                        <div class="flex gap-3">
                            <flux:button href="{{ route('debug.info') }}" variant="primary">Try Another PDF</flux:button>
                            <flux:button href="{{ route('offers.index') }}" variant="subtle">Back to Offers</flux:button>
                        </div>
                    </flux:card>
                </div>
            @endif
        </div>
</x-layouts.app>
