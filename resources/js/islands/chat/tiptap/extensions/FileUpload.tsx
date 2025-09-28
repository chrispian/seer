import { Extension } from '@tiptap/core'
import { Plugin, PluginKey } from '@tiptap/pm/state'

interface FileUploadOptions {
  onUpload: (file: File) => Promise<{ markdown: string }>
}

declare module '@tiptap/core' {
  interface Commands<ReturnType> {
    fileUpload: {
      insertFile: (file: File) => ReturnType
    }
  }
}

export const FileUpload = Extension.create<FileUploadOptions>({
  name: 'fileUpload',

  addOptions() {
    return {
      onUpload: async () => ({ markdown: '' }),
    }
  },

  addCommands() {
    return {
      insertFile:
        (file: File) =>
        ({ editor, tr, dispatch }) => {
          // Handle upload asynchronously
          this.options.onUpload(file)
            .then((result) => {
              if (result.markdown) {
                editor.commands.insertContent(result.markdown)
              }
            })
            .catch((error) => {
              console.error('File upload failed:', error)
            })
          
          return true
        },
    }
  },

  addProseMirrorPlugins() {
    return [
      new Plugin({
        key: new PluginKey('fileUpload'),
        props: {
          handleDOMEvents: {
            drop: (view, event) => {
              const files = Array.from(event.dataTransfer?.files || [])
              
              if (files.length > 0) {
                event.preventDefault()
                
                files.forEach(async (file) => {
                  try {
                    const result = await this.options.onUpload(file)
                    if (result.markdown) {
                      const { tr } = view.state
                      const pos = view.posAtCoords({ 
                        left: event.clientX, 
                        top: event.clientY 
                      })?.pos || view.state.selection.from
                      
                      tr.insertText(result.markdown, pos)
                      view.dispatch(tr)
                    }
                  } catch (error) {
                    console.error('File upload failed:', error)
                  }
                })
                
                return true
              }
              
              return false
            },
            
            paste: (view, event) => {
              const files = Array.from(event.clipboardData?.files || [])
              
              if (files.length > 0) {
                event.preventDefault()
                
                files.forEach(async (file) => {
                  try {
                    const result = await this.options.onUpload(file)
                    if (result.markdown) {
                      const { tr } = view.state
                      const { from } = view.state.selection
                      
                      tr.insertText(result.markdown, from)
                      view.dispatch(tr)
                    }
                  } catch (error) {
                    console.error('File upload failed:', error)
                  }
                })
                
                return true
              }
              
              return false
            },
          },
        },
      }),
    ]
  },
})

export async function uploadFile(file: File): Promise<{ markdown: string; url?: string }> {
  console.log('uploadFile called with:', file.name, file.size, file.type)
  
  const formData = new FormData()
  formData.append('file', file)
  
  // Get CSRF token
  const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
  console.log('CSRF token found:', !!csrfToken)
  
  if (!csrfToken) {
    throw new Error('CSRF token not found')
  }
  
  console.log('Making request to /api/files')
  const response = await fetch('/api/files', {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': csrfToken,
    },
    body: formData,
  })
  
  console.log('Response status:', response.status, response.statusText)
  
  if (!response.ok) {
    const text = await response.text()
    console.error('Upload failed response:', text)
    throw new Error(`Upload failed: ${response.statusText}`)
  }
  
  const data = await response.json()
  console.log('Upload response data:', data)
  
  if (!data.success) {
    throw new Error('Upload failed: ' + (data.message || 'Unknown error'))
  }
  
  return { markdown: data.markdown, url: data.url }
}