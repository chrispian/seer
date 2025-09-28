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
  onSend: (content: string, attachments?: Array<{markdown: string, url: string, filename: string}>) => void
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
  const [pendingAttachments, setPendingAttachments] = useState<Array<{markdown: string, url: string, filename: string}>>([])
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
    
    // Combine text content with file attachments
    if (trimmed || pendingAttachments.length > 0) {
      let finalContent = trimmed
      
      // Add file references at the end
      if (pendingAttachments.length > 0) {
        const fileReferences = pendingAttachments.map(att => att.markdown).join('\n')
        finalContent = trimmed ? `${trimmed}\n\n${fileReferences}` : fileReferences
      }
      
      onSend(finalContent, pendingAttachments)
      editor.commands.clearContent()
      setPendingAttachments([])
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
    if (!files) {
      console.log('No files selected')
      return
    }
    
    console.log('Files selected:', files.length)
    
    for (const file of Array.from(files)) {
      try {
        console.log('Starting upload for file:', file.name, 'size:', file.size, 'type:', file.type)
        
        const uploadResult = await uploadFile(file)
        console.log('Upload successful, result:', uploadResult)
        
        if (uploadResult.markdown) {
          // Add to pending attachments instead of inserting into editor
          const attachment = {
            markdown: uploadResult.markdown,
            url: uploadResult.url || '',
            filename: file.name
          }
          
          setPendingAttachments(prev => [...prev, attachment])
          console.log('Added attachment:', attachment)
        } else {
          console.error('No markdown in result:', uploadResult)
        }
      } catch (error) {
        console.error('File upload failed for', file.name, ':', error)
      }
    }
    
    // Reset input
    event.target.value = ''
  }

  const isEmpty = !editor?.getText().trim()

  return (
    <Card className="p-3">
      {/* Pending Attachments Preview */}
      {pendingAttachments.length > 0 && (
        <div className="mb-3 p-2 bg-muted rounded-md">
          <div className="text-xs text-muted-foreground mb-1">Attachments ({pendingAttachments.length}):</div>
          <div className="space-y-1">
            {pendingAttachments.map((attachment, index) => (
              <div key={index} className="flex items-center gap-2 text-xs">
                <Paperclip className="w-3 h-3" />
                <span className="flex-1 truncate">{attachment.filename}</span>
                <Button
                  variant="ghost"
                  size="icon"
                  className="h-4 w-4"
                  onClick={() => setPendingAttachments(prev => prev.filter((_, i) => i !== index))}
                >
                  ×
                </Button>
              </div>
            ))}
          </div>
        </div>
      )}
      
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
            disabled={disabled || (isEmpty && pendingAttachments.length === 0)}
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