<x-dynamic-component :component="config('chatbot.layout', 'app-layout')">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Audit log') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <form method="GET" action="{{ route('admin.audit') }}" class="bg-white shadow-sm sm:rounded-lg p-4 grid gap-3 md:grid-cols-5">
                <div>
                    <label for="tool" class="block text-sm text-gray-700">Tool</label>
                    <input id="tool" name="tool" value="{{ $filters['tool'] ?? '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                </div>
                <div>
                    <label for="user" class="block text-sm text-gray-700">User</label>
                    <input id="user" name="user" value="{{ $filters['user'] ?? '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                </div>
                <div>
                    <label for="success" class="block text-sm text-gray-700">Success</label>
                    <select id="success" name="success" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                        <option value="">Any</option>
                        <option value="1" @selected(($filters['success'] ?? '') === '1')>Yes</option>
                        <option value="0" @selected(($filters['success'] ?? '') === '0')>No</option>
                    </select>
                </div>
                <div>
                    <label for="from" class="block text-sm text-gray-700">From</label>
                    <input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                </div>
                <div>
                    <label for="to" class="block text-sm text-gray-700">To</label>
                    <input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="md:col-span-5">
                    <button type="submit" class="rounded-md bg-gray-800 px-4 py-2 text-sm text-white">Filter</button>
                </div>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-3 py-2">When</th>
                            <th class="px-3 py-2">User</th>
                            <th class="px-3 py-2">Tool</th>
                            <th class="px-3 py-2">Success</th>
                            <th class="px-3 py-2">Arguments</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr class="border-t">
                                <td class="px-3 py-2 whitespace-nowrap">{{ $log->created_at?->toDateTimeString() }}</td>
                                <td class="px-3 py-2">{{ $log->user?->email ?? 'deleted user' }}</td>
                                <td class="px-3 py-2">{{ $log->tool }}</td>
                                <td class="px-3 py-2">{{ $log->success ? 'Yes' : 'No' }}</td>
                                <td class="px-3 py-2"><pre class="whitespace-pre-wrap text-xs">{{ json_encode($log->args, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-gray-500">No tool calls match these filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $logs->links() }}</div>
        </div>
    </div>
</x-dynamic-component>
