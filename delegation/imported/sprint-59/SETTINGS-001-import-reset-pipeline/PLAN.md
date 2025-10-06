# SETTINGS-001 Implementation Plan: Import/Reset Settings Pipeline

## Phase 1: Backend Foundation (4-5 hours)

### 1.1 Create Import/Export Controller (1.5h)
```php
// app/Http/Controllers/Settings/ImportExportController.php
class ImportExportController extends Controller
{
    public function import(ImportSettingsRequest $request)
    public function reset(ResetSettingsRequest $request)
    public function generateResetToken()
}
```

### 1.2 Implement Request Validation (1h)
```php
// app/Http/Requests/Settings/ImportSettingsRequest.php
// app/Http/Requests/Settings/ResetSettingsRequest.php
- File validation rules
- JSON schema validation
- Security checks
```

### 1.3 Create Settings Service (1.5h)
```php
// app/Services/SettingsService.php
class SettingsService
{
    public function importSettings(User $user, array $data)
    public function resetSettings(User $user, array $sections)
    public function validateSettingsData(array $data)
    public function getDefaultSettings()
}
```

### 1.4 Add API Routes (30min)
```php
// routes/api.php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/settings/import', [ImportExportController::class, 'import']);
    Route::post('/settings/reset', [ImportExportController::class, 'reset']);
    Route::post('/settings/reset-token', [ImportExportController::class, 'generateResetToken']);
});
```

## Phase 2: Frontend Components (4-5 hours)

### 2.1 Enhance ImportExportControls (2h)
```typescript
// resources/js/islands/Settings/components/ImportExportControls.tsx
- Add import file picker
- Add reset confirmation dialog
- Wire up new API endpoints
- Handle loading states
```

### 2.2 Create Import Dialog (1.5h)
```typescript
// resources/js/islands/Settings/components/ImportDialog.tsx
- File selection interface
- Settings preview before import
- Confirmation with overwrite options
- Progress indicators
```

### 2.3 Create Reset Dialog (1h)
```typescript
// resources/js/islands/Settings/components/ResetDialog.tsx
- Section selection checkboxes
- Confirmation warning
- Reset token generation
- Success feedback
```

### 2.4 Add Import/Reset Hooks (30min)
```typescript
// resources/js/hooks/useSettingsImport.ts
// resources/js/hooks/useSettingsReset.ts
- API integration
- State management
- Error handling
```

## Phase 3: Validation & Security (2-3 hours)

### 3.1 Settings Schema Validation (1h)
```php
// app/Rules/ValidSettingsSchema.php
- JSON structure validation
- Field whitelist validation
- Value type checking
- Enum validation
```

### 3.2 File Upload Security (1h)
```php
// File validation in ImportSettingsRequest
- MIME type checking
- File size limits
- Content scanning
- Temporary file cleanup
```

### 3.3 Audit Logging (1h)
```php
// Add audit logging for import/reset operations
- Log successful imports with summary
- Log reset operations with sections
- Track failed attempts
- Include user context
```

## Phase 4: Testing & Error Handling (2-3 hours)

### 4.1 Backend Tests (1.5h)
```php
// tests/Feature/Settings/ImportExportTest.php
- Test valid import scenarios
- Test invalid file handling
- Test reset functionality
- Test authorization
```

### 4.2 Frontend Tests (1h)
```typescript
// Test dialog interactions
// Test file upload handling
// Test error states
// Test success flows
```

### 4.3 Error Handling Enhancement (30min)
```php
// Comprehensive error responses
// User-friendly error messages
// Rollback capabilities
// Graceful degradation
```

## Implementation Details

### Settings Import Logic
```php
public function importSettings(User $user, array $data): array
{
    DB::beginTransaction();
    
    try {
        $changes = [];
        
        // Import profile data
        if (isset($data['profile'])) {
            $profileChanges = $this->importProfileData($user, $data['profile']);
            $changes['profile'] = $profileChanges;
        }
        
        // Import preferences
        if (isset($data['preferences'])) {
            $prefChanges = $this->importPreferences($user, $data['preferences']);
            $changes['preferences'] = $prefChanges;
        }
        
        // Import AI settings
        if (isset($data['ai'])) {
            $aiChanges = $this->importAiSettings($user, $data['ai']);
            $changes['ai'] = $aiChanges;
        }
        
        DB::commit();
        return $changes;
        
    } catch (Exception $e) {
        DB::rollback();
        throw $e;
    }
}
```

### Reset Logic
```php
public function resetSettings(User $user, array $sections): array
{
    $defaults = $this->getDefaultSettings();
    $reset = [];
    
    foreach ($sections as $section) {
        switch ($section) {
            case 'preferences':
                $this->resetPreferences($user, $defaults['preferences']);
                $reset[] = 'preferences';
                break;
            case 'ai':
                $this->resetAiSettings($user, $defaults['ai']);
                $reset[] = 'ai';
                break;
            // Profile typically not reset for security
        }
    }
    
    return $reset;
}
```

### Frontend Import Flow
```typescript
const handleImport = async (file: File) => {
    try {
        setLoading(true);
        
        // Validate file
        if (!file.type.includes('json')) {
            throw new Error('Only JSON files are supported');
        }
        
        // Parse and preview
        const content = await file.text();
        const settings = JSON.parse(content);
        setPreviewData(settings);
        setShowPreview(true);
        
    } catch (error) {
        setError(error.message);
    } finally {
        setLoading(false);
    }
};

const confirmImport = async () => {
    try {
        const formData = new FormData();
        formData.append('file', selectedFile);
        
        const response = await api.post('/settings/import', formData);
        
        setSuccess(`Imported ${response.data.changes.length} settings`);
        onSettingsChanged();
        
    } catch (error) {
        setError(error.response?.data?.message || 'Import failed');
    }
};
```

## Success Metrics
- [ ] Import accepts valid JSON and updates settings correctly
- [ ] Reset restores defaults with proper confirmation
- [ ] File validation prevents malicious uploads
- [ ] Error messages guide users to resolution
- [ ] Audit logs capture all operations
- [ ] Integration tests cover all scenarios
- [ ] UI provides clear feedback throughout flow

## Dependencies
- Existing settings endpoints for validation patterns
- Current export format for import compatibility
- React dialog components for UI consistency
- Laravel file upload security practices