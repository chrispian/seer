# Link Command Pack

This command pack provides the `/link` slash command functionality.

## Usage

Type `/link` followed by your input to create a link fragment.

Example:
```
/link This is my input
```

## Configuration

The command is configured in `command.yaml`:

- **Triggers**: `/link`
- **Input Mode**: Inline
- **Capabilities**: fragment.create

## Steps

1. **coerce-input**: Processes user input from body or selection
2. **create-fragment**: Creates a new fragment with the processed content
3. **notify**: Shows success notification

## Testing

Test the command with:
```bash
php artisan frag:command:test link samples/basic.json --dry
```

## Customization

- Edit `command.yaml` to modify the command behavior
- Add prompts in `prompts/` directory for AI-powered processing
- Add sample inputs in `samples/` directory for testing