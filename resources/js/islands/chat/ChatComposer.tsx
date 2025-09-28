import React, { useRef, useState } from 'react'
import { useEditor, EditorContent } from '@tiptap/react'
import StarterKit from '@tiptap/starter-kit'
import { Markdown } from 'tiptap-markdown'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Mic, MicOff, Paperclip } from 'lucide-react'
import { 
  SlashCommand, 
  createSlashCommandSuggestion 
} from './tiptap/extensions/SlashCommand'
import { 
  WikiLink, 
  createWikiLinkSuggestion 
} from './tiptap/extensions/WikiLink'
import { 
  Hashtag, 
  createHashtagSuggestion 
} from './tiptap/extensions/Hashtag'
import { 
  FileUpload, 
  uploadFile 
} from './tiptap/extensions/FileUpload'

interface ChatComposerProps {
  onSend: (content: string) => void
  disabled?: boolean
  placeholder?: string
}

export function ChatComposer({ 
  onSend, 
  disabled = false, 
  placeholder = "Type a message... Use / for commands, [[ for links, # for tags" 
}: ChatComposerProps) {
  const [isListening, setIsListening] = useState(false)
  const [speechSupported, setSpeechSupported] = useState(false)
  const fileInputRef = useRef<HTMLInputElement>(null)
  const recognitionRef = useRef<SpeechRecognition | null>(null)

  React.useEffect(() => {
    // Check for speech recognition support
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition
    if (SpeechRecognition) {
      setSpeechSupported(true)
      const recognition = new SpeechRecognition()
      recognition.continuous = false
      recognition.interimResults = false
      recognition.lang = 'en-US'
      
      recognition.onresult = (event) => {
        const transcript = event.results[0][0].transcript
        if (editor && transcript) {
          editor.commands.insertContent(transcript + ' ')
        }
        setIsListening(false)
      }
      
      recognition.onerror = () => {
        setIsListening(false)
      }
      
      recognition.onend = () => {
        setIsListening(false)
      }
      
      recognitionRef.current = recognition
    }
  }, [])

  const editor = useEditor({
    extensions: [
      StarterKit,
      Markdown,
      SlashCommand.configure({
        suggestion: {
          ...createSlashCommandSuggestion(),
          command: ({ editor, range }: any) => {
            // Handle slash command execution
            const { from, to } = range
            editor.chain().focus().deleteRange({ from, to }).run()
          }
        }
      }),
      WikiLink.configure({
        suggestion: {
          ...createWikiLinkSuggestion(),
          command: ({ editor, range, props }: any) => {
            const { from, to } = range
            const wikiLink = `[[${props.value}]]`
            editor.chain().focus().deleteRange({ from, to }).insertContent(wikiLink).run()
          }
        }
      }),
      Hashtag.configure({
        suggestion: {
          ...createHashtagSuggestion(),
          command: ({ editor, range, props }: any) => {
            const { from, to } = range
            const hashtag = `#${props.value}`
            editor.chain().focus().deleteRange({ from, to }).insertContent(hashtag + ' ').run()
          }
        }
      }),
      FileUpload.configure({
        onUpload: uploadFile
      })
    ],
    content: '',
    editorProps: {
      attributes: {
        class: 'prose prose-sm max-w-none focus:outline-none min-h-[72px] p-3',
        placeholder
      }
    },
    onUpdate: ({ editor }) => {
      // Auto-resize behavior could be added here if needed
    }
  })

  const handleSend = () => {
    if (!editor || disabled) return
    
    const markdown = editor.storage.markdown.getMarkdown()
    const trimmed = markdown.trim()
    
    if (trimmed) {
      onSend(trimmed)
      editor.commands.clearContent()
    }
  }

  const handleKeyDown = (event: React.KeyboardEvent) => {
    if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
      event.preventDefault()
      handleSend()
    }
  }

  const handleVoiceInput = () => {
    if (!speechSupported || !recognitionRef.current) return
    
    if (isListening) {
      recognitionRef.current.stop()
    } else {
      setIsListening(true)
      recognitionRef.current.start()
    }
  }

  const handleFileUpload = () => {
    fileInputRef.current?.click()
  }

  const handleFileSelect = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const files = event.target.files
    if (!files || !editor) {
      console.log('No files or editor not ready')
      return
    }
    
    console.log('Files selected:', files.length)
    
    for (const file of Array.from(files)) {
      try {
        console.log('Starting upload for file:', file.name, 'size:', file.size, 'type:', file.type)
        
        // Show some loading state could be added here
        const result = await uploadFile(file)
        console.log('Upload successful, result:', result)
        
        if (result.markdown) {
          const success = editor.commands.insertContent(result.markdown + ' ')
          console.log('Markdown inserted:', result.markdown, 'success:', success)
          
          // Focus the editor after insertion
          editor.commands.focus()
        } else {
          console.error('No markdown in result:', result)
        }
      } catch (error) {
        console.error('File upload failed for', file.name, ':', error)
        // For now, insert a simple fallback
        editor.commands.insertContent(`[Upload failed: ${file.name}] `)
      }
    }
    
    // Reset input
    event.target.value = ''
  }

  const isEmpty = !editor?.getText().trim()

  return (
    <Card className="p-3">
      <div className="flex items-end gap-3">
        <div className="flex-1 relative">
          <EditorContent 
            editor={editor} 
            onKeyDown={handleKeyDown}
            className="border border-input rounded-md min-h-[72px] focus-within:border-ring"
          />
        </div>
        
        <div className="flex items-center gap-2">
          {/* File Upload Button */}
          <Button
            type="button"
            variant="outline"
            size="icon"
            onClick={handleFileUpload}
            disabled={disabled}
            title="Upload file"
          >
            <Paperclip className="w-4 h-4" />
          </Button>
          
          {/* Voice Input Button */}
          {speechSupported && (
            <Button
              type="button"
              variant="outline"
              size="icon"
              onClick={handleVoiceInput}
              disabled={disabled}
              title={isListening ? "Stop recording" : "Start voice input"}
              className={isListening ? "bg-red-100 border-red-300" : ""}
            >
              {isListening ? (
                <MicOff className="w-4 h-4 text-red-600" />
              ) : (
                <Mic className="w-4 h-4" />
              )}
            </Button>
          )}
          
          {/* Send Button */}
          <Button 
            onClick={handleSend} 
            disabled={disabled || isEmpty}
            className="min-w-[80px]"
          >
            {disabled ? 'Sending…' : 'Send'}
          </Button>
        </div>
      </div>
      
      {/* Hidden file input */}
      <input
        ref={fileInputRef}
        type="file"
        className="hidden"
        onChange={handleFileSelect}
        multiple
        accept="image/*,.pdf,.txt,.md,.doc,.docx"
      />
    </Card>
  )
}