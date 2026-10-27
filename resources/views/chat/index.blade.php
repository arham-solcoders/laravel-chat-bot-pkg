<x-dynamic-component :component="config('chatbot.layout', 'app-layout')">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Database chat') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div
                class="grid gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]"
                x-data="chatWidget({{ \Illuminate\Support\Js::from([
                    'messages' => $messages,
                    'quickReplies' => $quickReplies,
                    'initialQuickReplies' => $suggestions,
                    'maxLength' => $maxLength,
                ]) }})"
            >
                <aside class="bg-white shadow-sm sm:rounded-lg p-4">
                    <h3 class="font-medium text-gray-900">You can ask</h3>
                    <ul class="mt-3 space-y-2 text-sm text-gray-700">
                        @foreach ($suggestions as $suggestion)
                            <li>
                                <button type="button" class="text-left hover:underline" @click="send(@js($suggestion))">
                                    {{ $suggestion }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                    @can('viewAudit')
                        <a href="{{ route('admin.audit') }}" class="mt-4 inline-block text-sm text-gray-600 underline">Audit log</a>
                    @endcan
                </aside>

                <section class="bg-white shadow-sm sm:rounded-lg flex flex-col min-h-[36rem]">
                    <div class="flex justify-end border-b px-4 py-2">
                        <button type="button" class="text-sm text-gray-600 underline" @click="clearChat()">
                            Clear chat
                        </button>
                    </div>
                    <div x-ref="log" class="flex-1 overflow-y-auto p-4 space-y-3" aria-live="polite">
                        <template x-for="(message, index) in messages" :key="index">
                            <div class="flex" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
                                <div class="max-w-[85%] rounded-lg px-3 py-2 text-sm" :class="message.role === 'user' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-900'">
                                    <p class="whitespace-pre-wrap" x-text="message.content"></p>
                                    <template x-if="message.type === 'table' && message.rows && message.rows.length">
                                        <div class="mt-2 overflow-x-auto">
                                            <table class="min-w-full text-left text-xs text-gray-800">
                                                <thead>
                                                    <tr>
                                                        <template x-for="column in columnsFor(message)" :key="column.key">
                                                            <th class="border-b px-2 py-1 font-medium" x-text="column.label"></th>
                                                        </template>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="(row, rowIndex) in message.rows" :key="rowIndex">
                                                        <tr>
                                                            <template x-for="column in columnsFor(message)" :key="column.key">
                                                                <td class="border-b px-2 py-1" x-text="row[column.key] ?? ''"></td>
                                                            </template>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </template>
                                    <template x-if="safeUrl(message.fileUrl)">
                                        <a class="mt-2 inline-block underline" :href="safeUrl(message.fileUrl)">Download</a>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <p x-show="loading" class="text-sm text-gray-500" x-cloak>Typing…</p>
                    </div>

                    <div class="border-t p-4 space-y-3">
                        <p x-show="error" x-text="error" class="text-sm text-red-600" x-cloak></p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="reply in quickReplies" :key="reply">
                                <button type="button" class="rounded-full border px-3 py-1 text-sm text-gray-700 hover:bg-gray-50" @click="send(reply)" x-text="reply"></button>
                            </template>
                        </div>
                        <form class="flex gap-2" @submit.prevent="send()">
                            <label class="sr-only" for="chat-message">Message</label>
                            <input id="chat-message" x-model="draft" :maxlength="maxLength" type="text" class="flex-1 rounded-md border-gray-300 text-sm" placeholder="Ask about an order, stock, or sales" autocomplete="off">
                            <button type="submit" class="rounded-md bg-gray-800 px-4 py-2 text-sm text-white disabled:opacity-50" :disabled="loading">Send</button>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('alpine:init', () => {
            window.Alpine.data('chatWidget', (initial) => ({
                messages: initial.messages || [],
                quickReplies: initial.quickReplies || [],
                initialQuickReplies: initial.initialQuickReplies || [],
                maxLength: initial.maxLength || 500,
                draft: '',
                loading: false,
                error: '',

                columnsFor(message) {
                    if (message.columns && message.columns.length) {
                        return message.columns;
                    }

                    const row = (message.rows || [])[0] || {};

                    return Object.keys(row).map((key) => ({ key, label: key }));
                },

                safeUrl(url) {
                    if (typeof url !== 'string' || url === '') {
                        return '';
                    }

                    try {
                        const parsed = new URL(url, window.location.origin);

                        if (parsed.origin !== window.location.origin || !parsed.pathname.startsWith('/reports/')) {
                            return '';
                        }

                        return parsed.pathname + parsed.search;
                    } catch (error) {
                        return '';
                    }
                },

                scroll() {
                    if (this.$refs.log) {
                        this.$refs.log.scrollTop = this.$refs.log.scrollHeight;
                    }
                },

                async send(text = null) {
                    const message = String(text ?? this.draft).trim();

                    if (!message || this.loading) {
                        return;
                    }

                    this.draft = '';
                    this.error = '';
                    this.messages.push({
                        role: 'user',
                        content: message,
                        type: 'text',
                        rows: [],
                        columns: [],
                        fileUrl: null,
                        quickReplies: [],
                    });
                    this.loading = true;
                    this.$nextTick(() => this.scroll());

                    try {
                        const response = await fetch('/chat', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ message }),
                        });

                        if (response.status === 422) {
                            this.error = 'That message is too long.';
                            return;
                        }

                        if (response.status === 429) {
                            this.error = 'Too many messages. Wait a minute and try again.';
                            return;
                        }

                        if (!response.ok) {
                            this.error = 'The chat could not answer just now.';
                            return;
                        }

                        const data = await response.json();
                        this.messages.push({
                            role: 'assistant',
                            content: data.message || '',
                            type: data.type || 'text',
                            rows: data.rows || [],
                            columns: data.columns || [],
                            fileUrl: data.fileUrl || null,
                            quickReplies: data.quickReplies || [],
                        });
                        this.quickReplies = data.quickReplies || [];
                    } catch (error) {
                        this.error = 'The chat could not answer just now.';
                    } finally {
                        this.loading = false;
                        this.$nextTick(() => this.scroll());
                    }
                },

                async clearChat() {
                    const response = await fetch('/chat', {
                        method: 'DELETE',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });

                    if (!response.ok) {
                        this.error = 'The chat could not be cleared.';
                        return;
                    }

                    const data = await response.json();
                    this.messages = [];
                    this.quickReplies = data.quickReplies || this.initialQuickReplies;
                    this.error = '';
                },
            }));
        });
    </script>
</x-dynamic-component>
