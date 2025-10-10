# UI Testing Commands

## Reset for Setup Wizard Testing
```bash
php artisan tinker --execute="App\Models\User::first()->update(['profile_completed_at' => null]);"
```
Then visit: http://seer.test/

## Mark Setup Complete for Settings Testing  
```bash
php artisan tinker --execute="App\Models\User::first()->update(['profile_completed_at' => now()]);"
```
Then visit: http://seer.test/settings

## Test Avatar Service
```bash
php artisan tinker --execute="
\$user = App\Models\User::first();
\$service = app(App\Services\AvatarService::class);
echo 'Avatar URL: ' . \$service->getAvatarUrl(\$user);
"
```

## Test Settings Export
Visit: http://seer.test/settings/export

## Clean Up Avatars
```bash
php artisan avatars:cleanup --days=0
```

## Check Routes
```bash
php artisan route:list --name=setup
php artisan route:list --name=settings
```

## URLs to Test

### Setup Wizard Flow
- http://seer.test/setup/welcome
- http://seer.test/setup/profile
- http://seer.test/setup/avatar
- http://seer.test/setup/preferences
- http://seer.test/setup/complete

### Settings Interface
- http://seer.test/settings (main interface)
- http://seer.test/settings/export (download settings JSON)

### Main App
- http://seer.test/ (should redirect to setup if incomplete)

## Expected Behaviors

1. **Middleware Redirect**: Incomplete users redirected to setup
2. **Setup Flow**: Smooth progression through wizard steps
3. **Form Validation**: Real-time validation in forms
4. **Avatar Upload**: Drag-and-drop with preview
5. **Settings Persistence**: Changes saved immediately
6. **Responsive Design**: Works on mobile, tablet, desktop