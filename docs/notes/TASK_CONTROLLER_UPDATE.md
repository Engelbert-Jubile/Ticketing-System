# Update TaskController untuk Multi-Assignees dan Requester Selection

## Perubahan yang Diperlukan di `App/Http/Controllers/Main/TaskController.php`

### 1. Update Validation Rules (Line ~78-88)

**GANTI:**
```php
$request->validate([
    'output_type'   => ['nullable', 'in:task,task_project'],
    'project_title' => ['nullable', 'string', 'max:255'],
    'project_start' => ['nullable', 'date'],
    'project_end'   => ['nullable', 'date', 'after_or_equal:project_start'],

    'priority'      => ['nullable', 'in:low,normal,high,critical'],
    'assignee_id'   => ['nullable', 'integer', 'exists:users,id'],
    'assigned_to'   => ['nullable', 'string', 'max:255'],
    'due_at'        => ['nullable', 'string'],
]);
```

**DENGAN:**
```php
$request->validate([
    'output_type'   => ['nullable', 'in:task,task_project'],
    'project_title' => ['nullable', 'string', 'max:255'],
    'project_start' => ['nullable', 'date'],
    'project_end'   => ['nullable', 'date', 'after_or_equal:project_start'],

    'priority'      => ['nullable', 'in:low,normal,high,critical'],
    'assignees'     => ['nullable', 'array'],
    'assignees.*'   => ['integer', 'exists:users,id'],
    'requester_id'  => ['nullable', 'integer', 'exists:users,id'],
    'due_at'        => ['nullable', 'string'],
]);
```

### 2. Handle Requester ID & Update DB Transaction (Line ~95-115)

**TAMBAHKAN SEBELUM** `$task = DB::transaction(...`:

```php
// Handle requester_id: jika superadmin/admin set custom requester, atau gunakan current user
$requesterId = null;
if ($request->filled('requester_id')) {
    $currentUser = Auth::user();
    if ($currentUser && method_exists($currentUser, 'hasAnyRole')) {
        if ($currentUser->hasAnyRole(['superadmin', 'Super Admin']) || 
            (method_exists($currentUser, 'hasRole') && $currentUser->hasRole('Admin'))) {
            $requesterId = $request->input('requester_id');
        }
    }
}
if (!$requesterId && Auth::check()) {
    $requesterId = Auth::id();
}
```

### 3. Update DB::transaction Parameter

**GANTI:**
```php
$task = DB::transaction(function () use ($request, $data) {
```

**DENGAN:**
```php
$task = DB::transaction(function () use ($request, $data, $requesterId) {
```

### 4. Update di dalam Transaction Closure

**SETELAH** `$task = $this->createTask->execute($data);` **TAMBAHKAN:**

```php
            // Set requester (created_by)
            if ($requesterId) {
                $task->created_by = $requesterId;
                $task->save();
            }
```

**GANTI:**
```php
            // Apply META (priority/assignee/due) pasca create
            $meta = $this->extractMeta($request);
            if (!empty($meta)) {
                $task->fill($meta)->save();
            }
```

**DENGAN:**
```php
            // Apply META (priority/assignees/due) pasca create
            $meta = $this->extractMeta($request);
            
            // Handle multi-assignees - simpan assignee pertama sebagai assignee_id
            $assignees = $request->input('assignees', []);
            if (!empty($assignees)) {
                $meta['assignee_id'] = $assignees[0];
                // TODO: Jika ada relasi task_assignees, bisa simpan semua di sini
            }

            if (!empty($meta)) {
                $task->fill($meta)->save();
            }
```

### 5. Update Notifications untuk Multi-Assignees

**GANTI:**
```php
            if ($task->assignee_id) {
                $assignee = \App\Models\User::find($task->assignee_id);
                if ($assignee && (!$creator || $assignee->id !== $creator->id)) {
                    $assignee->notify(new ActivityNotification([
                        'title'        => 'Task Assigned',
                        'message'      => 'Anda ditugaskan pada task: ' . $task->title,
                        'url'          => $url,
                        'icon'         => 'assignment',
                        'by'           => $creator?->display_name,
                        'subject_type' => 'task',
                        'subject_id'   => $task->id,
                    ]));
                }
            }
```

**DENGAN:**
```php
            // Notify all assignees
            $assignees = $request->input('assignees', []);
            foreach ($assignees as $assigneeId) {
                $assignee = \App\Models\User::find($assigneeId);
                if ($assignee && (!$creator || $assignee->id !== $creator->id)) {
                    $assignee->notify(new ActivityNotification([
                        'title'        => 'Task Assigned',
                        'message'      => 'Anda ditugaskan pada task: ' . $task->title,
                        'url'          => $url,
                        'icon'         => 'assignment',
                        'by'           => $creator?->display_name,
                        'subject_type' => 'task',
                        'subject_id'   => $task->id,
                    ]));
                }
            }
```

## Yang Sudah Diubah di View

✅ Field "Orang lain (opsional)" **DIHAPUS**
✅ "Assign ke User" berubah menjadi **MULTI-SELECT CHECKBOX**
✅ Field "Requester" **DITAMBAH** (hanya untuk superadmin/admin)
✅ Dark mode support sudah ada

## Notes

- Assignee pertama yang dipilih akan disimpan di field `assignee_id`
- Jika ada relasi `task_assignees` di masa depan, bisa mengubah bagian TODO untuk simpan semua assignee
- Requester hanya bisa diubah oleh superadmin/admin
- Jika requester_id tidak dipilih, otomatis yang membuat task menjadi requester
