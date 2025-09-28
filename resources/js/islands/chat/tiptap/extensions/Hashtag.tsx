import { Extension } from '@tiptap/core'
import { PluginKey } from '@tiptap/pm/state'
import Suggestion from '@tiptap/suggestion'
import { ReactRenderer } from '@tiptap/react'
import tippy, { Instance, Props } from 'tippy.js'
import React, { forwardRef, useEffect, useImperativeHandle, useState } from 'react'
import { Command, CommandEmpty, CommandGroup, CommandItem, CommandList } from '@/components/ui/command'
import { fetchHashtags, AutocompleteResult } from '../utils/autocomplete'

export interface HashtagProps {
  items: AutocompleteResult[]
  command: (item: AutocompleteResult) => void
}

export interface HashtagRef {
  onKeyDown: (props: { event: KeyboardEvent }) => boolean
}

const HashtagList = forwardRef<HashtagRef, HashtagProps>(
  ({ items, command }, ref) => {
    const [selectedIndex, setSelectedIndex] = useState(0)

    const selectItem = (index: number) => {
      const item = items[index]
      if (item) {
        command(item)
      }
    }

    const upHandler = () => {
      setSelectedIndex((selectedIndex + items.length - 1) % items.length)
    }

    const downHandler = () => {
      setSelectedIndex((selectedIndex + 1) % items.length)
    }

    const enterHandler = () => {
      selectItem(selectedIndex)
    }

    useEffect(() => setSelectedIndex(0), [items])

    useImperativeHandle(ref, () => ({
      onKeyDown: ({ event }) => {
        if (event.key === 'ArrowUp') {
          upHandler()
          return true
        }

        if (event.key === 'ArrowDown') {
          downHandler()
          return true
        }

        if (event.key === 'Enter') {
          enterHandler()
          return true
        }

        return false
      },
    }))

    return (
      <Command className="w-60">
        <CommandList className="max-h-[200px] overflow-y-auto">
          {items.length ? (
            <CommandGroup>
              {items.map((item, index) => (
                <CommandItem
                  className={`cursor-pointer ${
                    index === selectedIndex ? 'bg-accent' : ''
                  }`}
                  key={index}
                  onSelect={() => selectItem(index)}
                >
                  <div className="flex items-center space-x-2">
                    <span className="bg-primary/10 text-primary px-2 py-1 rounded text-sm">
                      {item.display}
                    </span>
                    {item.description && (
                      <span className="text-xs text-muted-foreground">{item.description}</span>
                    )}
                  </div>
                </CommandItem>
              ))}
            </CommandGroup>
          ) : (
            <CommandEmpty>No tags found</CommandEmpty>
          )}
        </CommandList>
      </Command>
    )
  }
)

HashtagList.displayName = 'HashtagList'

export const Hashtag = Extension.create({
  name: 'hashtag',

  addOptions() {
    return {
      suggestion: {
        char: '#',
        command: ({ editor, range, props }: any) => {
          props.command({ editor, range })
        },
      },
    }
  },

  addProseMirrorPlugins() {
    return [
      Suggestion({
        editor: this.editor,
        ...this.options.suggestion,
        pluginKey: new PluginKey('hashtag'),
      }),
    ]
  },
})

export const createHashtagSuggestion = () => ({
  items: async ({ query }: { query: string }) => {
    return await fetchHashtags(query)
  },

  render: () => {
    let component: ReactRenderer<HashtagRef>
    let popup: Instance<Props>[]

    return {
      onStart: (props: any) => {
        component = new ReactRenderer(HashtagList, {
          props,
          editor: props.editor,
        })

        if (!props.clientRect) {
          return
        }

        popup = tippy('body', {
          getReferenceClientRect: props.clientRect,
          appendTo: () => document.body,
          content: component.element,
          showOnCreate: true,
          interactive: true,
          trigger: 'manual',
          placement: 'bottom-start',
        })
      },

      onUpdate(props: any) {
        component.updateProps(props)

        if (!props.clientRect) {
          return
        }

        popup[0].setProps({
          getReferenceClientRect: props.clientRect,
        })
      },

      onKeyDown(props: any) {
        if (props.event.key === 'Escape') {
          popup[0].hide()
          return true
        }

        return component.ref?.onKeyDown(props) || false
      },

      onExit() {
        popup[0].destroy()
        component.destroy()
      },
    }
  },
})