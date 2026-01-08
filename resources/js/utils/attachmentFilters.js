export const attachmentFilterOptions = [
  {
    value: 'pdf',
    label: 'PDF (.pdf)',
    description: 'Dokumen PDF',
    extensions: ['pdf'],
    mimeTypes: ['application/pdf'],
  },
  {
    value: 'images',
    label: 'Gambar (.jpg, .jpeg, .png)',
    description: 'File gambar JPG atau PNG',
    extensions: ['jpg', 'jpeg', 'png'],
    mimeTypes: ['image/jpeg', 'image/png'],
  },
  {
    value: 'word',
    label: 'Dokumen Word (.doc, .docx)',
    description: 'Dokumen Microsoft Word',
    extensions: ['doc', 'docx'],
    mimeTypes: [
      'application/msword',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ],
  },
  {
    value: 'excel',
    label: 'Spreadsheet (.xls, .xlsx)',
    description: 'Dokumen Microsoft Excel',
    extensions: ['xls', 'xlsx'],
    mimeTypes: [
      'application/vnd.ms-excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ],
  },
  {
    value: 'powerpoint',
    label: 'Presentasi (.ppt, .pptx)',
    description: 'Dokumen Microsoft PowerPoint',
    extensions: ['ppt', 'pptx'],
    mimeTypes: [
      'application/vnd.ms-powerpoint',
      'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ],
  },
  {
    value: 'archives',
    label: 'Arsip (.zip, .rar)',
    description: 'Berkas arsip terkompresi',
    extensions: ['zip', 'rar'],
    mimeTypes: ['application/zip', 'application/x-rar-compressed'],
  },
  {
    value: 'text',
    label: 'Teks (.txt, .csv)',
    description: 'Berkas teks atau CSV',
    extensions: ['txt', 'csv'],
    mimeTypes: ['text/plain', 'text/csv'],
  },
];

export const defaultAttachmentExtensions = attachmentFilterOptions.flatMap(option => option.extensions);
