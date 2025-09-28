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
        async ({ tr, dispatch }) => {
          try {
            const result = await this.options.onUpload(file)
            if (dispatch && result.markdown) {
              const { from } = tr.selection
              tr.insertText(result.markdown, from)
              return true
            }
            return false
          } catch (error) {
            console.error('File upload failed:', error)
            return false
          }
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

export async function uploadFile(file: File): Promise<{ markdown: string }> {
  const formData = new FormData()
  formData.append('file', file)
  
  // Get CSRF token
  const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
  
  const response = await fetch('/api/files', {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': csrfToken,
    },
    body: formData,
  })
  
  if (!response.ok) {
    throw new Error(`Upload failed: ${response.statusText}`)
  }
  
  const data = await response.json()
  
  if (!data.success) {
    throw new Error('Upload failed')
  }
  
  return { markdown: data.markdown }
}