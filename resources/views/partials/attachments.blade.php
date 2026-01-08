@php
/** @var \Illuminate\Support\Collection|\App\Models\Attachment[] $initialAttachments */
$initialAttachments = $initialAttachments ?? collect();
$toggleDefault = $toggleDefault ?? false;
$inputId = $inputId ?? 'attachments';
@endphp

<div class="mb-3 flex items-center justify-between">
  <label class="mb-0 font-semibold">Lampiran (opsional)</label>
  <div class="form-check form-switch">
    <input class="form-check-input" type="checkbox" id="toggle-{{ $inputId }}" {{ $toggleDefault ? 'checked' : '' }}>
    <label class="form-check-label" for="toggle-{{ $inputId }}">Tambahkan lampiran</label>
  </div>
</div>

<div id="wrap-{{ $inputId }}" class="mb-3 attachment-wrap {{ $toggleDefault ? '' : 'd-none' }}">
  <input
    type="file"
    id="{{ $inputId }}"
    name="file"
    class="filepond"
    multiple
    accept="image/jpeg,image/jpg,image/png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/zip,application/x-rar-compressed,text/plain"
  />
  <small class="text-muted d-block mt-2">
    PDF, JPG/PNG, DOC/DOCX, XLS/XLSX, PPT/PPTX, ZIP/RAR, TXT • maks 10MB per file • seret &amp; lepas atau klik untuk memilih
  </small>
</div>

@if ($initialAttachments->isNotEmpty())
  <div class="mb-3">
    <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
      <div class="sticky top-0 z-10 border-b border-slate-200 bg-white/90 px-3 py-2 text-sm font-semibold dark:border-slate-700 dark:bg-slate-900/90">
        Lampiran saat ini
      </div>
      <ul class="max-h-64 divide-y divide-slate-200 overflow-y-auto bg-white/80 dark:divide-slate-700 dark:bg-slate-900/70">
        @foreach ($initialAttachments as $att)
          <li class="flex items-center justify-between gap-3 px-3 py-2">
            <div class="truncate">
              <span class="font-medium">{{ $att->original_name }}</span>
              <span class="ml-2 text-xs text-gray-500">({{ number_format(($att->size ?? 0) / 1024, 0) }} KB)</span>
            </div>
            <div class="flex items-center gap-2">
              <a
                class="inline-flex items-center gap-1 rounded-lg border border-blue-200 px-3 py-1.5 text-sm text-blue-700 hover:bg-blue-50 dark:border-blue-800 dark:text-blue-300 dark:hover:bg-blue-900/30"
                href="{{ route('attachments.view', $att) }}"
                target="_blank"
              >Lihat</a>
              <a
                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800/60"
                href="{{ route('attachments.download', $att) }}"
              >Unduh</a>
              <button
                type="button"
                class="attachment-delete-btn inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-1.5 text-sm text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-900/30"
                data-url="{{ route('attachments.destroy', $att) }}"
                data-confirm="Hapus lampiran ini?"
              >Hapus</button>
            </div>
          </li>
        @endforeach
      </ul>
    </div>
  </div>
@endif

@push('styles')
  <link href="{{ asset('vendor/filepond/filepond.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/filepond/filepond-plugin-image-preview.min.css') }}" rel="stylesheet">
  <style>
    .attachment-wrap {
      position: relative;
      margin-top: .25rem;
      margin-bottom: 1rem;
    }

    .attachment-wrap .filepond--root {
      width: 100%;
      max-width: 100%;
      min-height: 180px;
      border: 1px dashed rgba(148, 163, 184, 0.45);
      border-radius: .75rem;
      background: rgba(248, 250, 252, 0.9);
    }

    .attachment-wrap .filepond--panel-root {
      background-color: transparent;
    }

    .attachment-wrap .filepond--drop-label {
      color: #64748b;
    }

    .dark .attachment-wrap .filepond--drop-label {
      color: #94a3b8;
    }
  </style>
@endpush

@push('scripts')
  <script src="{{ asset('vendor/filepond/filepond.min.js') }}"></script>
  <script src="{{ asset('vendor/filepond/filepond-plugin-file-validate-type.min.js') }}"></script>
  <script src="{{ asset('vendor/filepond/filepond-plugin-file-validate-size.min.js') }}"></script>
  <script src="{{ asset('vendor/filepond/filepond-plugin-image-preview.min.js') }}"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const input = document.getElementById('{{ $inputId }}');
      const toggle = document.getElementById('toggle-{{ $inputId }}');
      const wrap = document.getElementById('wrap-{{ $inputId }}');
      const deleteButtons = document.querySelectorAll('.attachment-delete-btn');

      if (deleteButtons.length) {
        deleteButtons.forEach((btn) => {
          btn.addEventListener('click', () => {
            const url = btn.dataset.url;
            const message = btn.dataset.confirm || 'Hapus lampiran ini?';
            if (!url) {
              return;
            }
            if (!window.confirm(message)) {
              return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = "{{ csrf_token() }}";
            form.appendChild(csrfInput);

            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);

            document.body.appendChild(form);
            form.submit();
          });
        });
      }

      if (!input || !toggle || !wrap) {
        console.warn('Attachment elements not found, skipping FilePond setup.');
        return;
      }

      const storageKey = `filepond_{{ $inputId }}`;
      wrap.classList.toggle('d-none', !toggle.checked);

      toggle.addEventListener('change', (event) => {
        wrap.classList.toggle('d-none', !event.target.checked);
      });

      FilePond.registerPlugin(
        FilePondPluginImagePreview,
        FilePondPluginFileValidateSize,
        FilePondPluginFileValidateType
      );

      if (input._pond) {
        input._pond.destroy();
      }

      const acceptedTypes = [
        'image/jpeg',
        'image/png',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/zip',
        'application/x-rar-compressed',
        'text/plain'
      ];

      const pond = FilePond.create(input, {
        name: 'file',
        credits: false,
        allowMultiple: true,
        allowReorder: true,
        instantUpload: true,
        allowFileTypeValidation: true,
        allowFileSizeValidation: true,
        maxFiles: 10,
        maxFileSize: '10MB',
        acceptedFileTypes: acceptedTypes,
        server: {
          process: {
            url: "{{ route('attachments.process') }}",
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': "{{ csrf_token() }}",
              'Accept': 'application/json'
            },
            ondata: (formData) => formData,
            onload: (response) => {
              try {
                const result = JSON.parse(response);
                return result.id ?? response;
              } catch (error) {
                console.error('Failed to parse upload response', error);
                return response;
              }
            },
            onerror: (response) => {
              try {
                const payload = JSON.parse(response);
                return payload.message || 'Upload gagal';
              } catch (error) {
                console.error('Upload error', error);
                return response;
              }
            }
          },
          revert: {
            url: "{{ route('attachments.revert') }}",
            method: 'DELETE',
            headers: {
              'X-CSRF-TOKEN': "{{ csrf_token() }}",
              'Accept': 'application/json'
            },
            onload: () => persistState()
          },
          restore: "{{ url('/dashboard/attachments/tmp') }}/",
          load: "{{ url('/dashboard/attachments/tmp') }}/",
          fetch: null
        },
        labelFileTypeNotAllowed: 'File type tidak diizinkan',
        fileValidateTypeLabelExpectedTypes: 'File yang diizinkan: {allTypes}',
        labelMaxFileSizeExceeded: 'File terlalu besar',
        labelMaxFileSize: 'Ukuran maksimal: {filesize}',
        labelIdle: 'Seret & lepas file atau <span class="filepond--label-action">Pilih File</span>'
      });

      function persistState() {
        const files = pond
          .getFiles()
          .map((file) => {
            const source = file.serverId || file.source;
            if (!source) {
              return null;
            }

            return {
              source,
              options: {
                type: 'local',
                metadata: {
                  name: file.filename
                }
              }
            };
          })
          .filter(Boolean);

        if (files.length > 0) {
          localStorage.setItem(storageKey, JSON.stringify(files));
        } else {
          localStorage.removeItem(storageKey);
        }
      }

      pond.on('processfile', persistState);
      pond.on('removefile', persistState);

      const saved = localStorage.getItem(storageKey);
      if (saved) {
        try {
          const files = JSON.parse(saved);
          files.forEach((file) => {
            if (file && file.source) {
              pond.addFile(file.source, file.options || { type: 'local' });
            }
          });
        } catch (error) {
          console.error('Failed to restore FilePond state', error);
          localStorage.removeItem(storageKey);
        }
      }

      const form = input.closest('form');
      if (form) {
        form.addEventListener('submit', () => {
          localStorage.removeItem(storageKey);
        });
      }

      input._pond = pond;
    });
  </script>
@endpush
