{{-- resources/views/components/task/promote-button.blade.php --}}
@props([
  'task',
  'size' => 'md',
  'class' => '',
])

@php
  $disabled = (bool) ($task->project_id ?? false);
  $sizes = [
    'sm' => 'px-2 py-1 text-xs rounded',
    'md' => 'px-3 py-2 text-sm rounded',
    'lg' => 'px-4 py-2 text-sm rounded-lg',
  ];
@endphp

<form action="{{ route('tasks.promote', $task) }}"
      method="POST"
      onsubmit="return confirm('Promote task ini menjadi project?')"
      class="inline-block {{ $class }}">
  @csrf
  <button type="submit"
          @if($disabled) disabled @endif
          title="{{ $disabled ? 'Task sudah terhubung ke Project' : 'Promote ke Project' }}"
          class="{{ $sizes[$size] ?? $sizes['md'] }}
                 inline-flex items-center gap-1
                 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400
                 text-white disabled:text-white/80
                 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
      <path d="M13 7h-2v10h2V7Zm-2-4h2v2h-2V3Zm8.95 4.05L19.54 7.5A8 8 0 1 1 16.5 4.46l1.45-1.41A10 10 0 1 0 22 12c0-2.65-1.03-5.05-2.83-6.95Z"/>
    </svg>
    Promote to Project
  </button>
</form>
