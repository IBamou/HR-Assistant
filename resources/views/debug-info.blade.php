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
                        <flux:heading size="sm" class="mb-3">Extracted Text Length</flux:heading>
                        <flux:text>{{ $result['extracted_text_length'] }} characters</flux:text>
                        @if (($result['extracted_text_length'] ?? 0) < 50)
                            <div class="mt-2 rounded-lg bg-amber-50 p-3 text-amber-700 text-sm">
                                Text too short — may be a scanned image.
                            </div>
                        @endif
                    </flux:card>

                    <flux:card>
                        <flux:heading size="sm" class="mb-3">LlamaParse / Extracted Text</flux:heading>
                        @if (isset($result['llama_error']))
                            <div class="rounded-lg bg-red-50 p-3 text-red-700 text-sm mb-2">LlamaParse error: {{ $result['llama_error'] }}</div>
                        @endif
                        @if (isset($result['fallback_used']))
                            <div class="rounded-lg bg-amber-50 p-3 text-amber-700 text-sm mb-2">Used PdfExtractor fallback.</div>
                        @endif
                        @if (isset($result['llama_data']['raw_keys']))
                            <div class="mb-2 text-sm text-zinc-500">API response keys: {{ implode(', ', $result['llama_data']['raw_keys']) }}</div>
                        @endif
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
                        <div class="flex gap-3">
                            <flux:button href="{{ route('debug.info') }}" variant="primary">Try Another PDF</flux:button>
                            <flux:button href="{{ route('offers.index') }}" variant="subtle">Back to Offers</flux:button>
                        </div>
                    </flux:card>
                </div>
            @endif
        </div>
</x-layouts.app>
