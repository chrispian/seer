import { CardConfig, ScrollAreaConfig, ResizableConfig, AspectRatioConfig, CollapsibleConfig, AccordionConfig } from '../types';

export const cardExample: CardConfig = {
  id: 'card-1',
  type: 'card',
  props: {
    title: 'Card Title',
    description: 'This is a card description',
    className: 'w-96',
  },
  children: [
    {
      id: 'card-content-1',
      type: 'typography.p',
      props: {
        text: 'This is the card body content. You can add any components here.',
      },
    },
  ],
};

export const cardWithFooterExample: CardConfig = {
  id: 'card-2',
  type: 'card',
  props: {
    title: 'Settings',
    description: 'Manage your account settings',
    footer: {
      id: 'footer-button',
      type: 'button',
      props: {
        label: 'Save Changes',
        variant: 'default',
      },
    },
    className: 'w-96',
  },
  children: [
    {
      id: 'setting-1',
      type: 'field',
      props: {
        label: 'Username',
        helperText: 'This is your public display name',
      },
      children: [
        {
          id: 'username-input',
          type: 'input.text',
          props: {
            placeholder: 'Enter username',
          },
        },
      ],
    },
  ],
};

export const scrollAreaExample: ScrollAreaConfig = {
  id: 'scroll-1',
  type: 'scroll-area',
  props: {
    height: '300px',
    className: 'border rounded-md',
  },
  children: [
    {
      id: 'scroll-content',
      type: 'typography.p',
      props: {
        text: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. '.repeat(20),
      },
    },
  ],
};

export const resizableExample: ResizableConfig = {
  id: 'resizable-1',
  type: 'resizable',
  props: {
    direction: 'horizontal',
    withHandle: true,
    className: 'h-96 border rounded-md',
    panels: [
      {
        id: 'panel-1',
        defaultSize: 50,
        minSize: 30,
        content: [
          {
            id: 'panel-1-content',
            type: 'card',
            props: {
              title: 'Left Panel',
            },
            children: [
              {
                id: 'left-text',
                type: 'typography.p',
                props: {
                  text: 'This is the left panel content',
                },
              },
            ],
          },
        ],
      },
      {
        id: 'panel-2',
        defaultSize: 50,
        minSize: 30,
        content: [
          {
            id: 'panel-2-content',
            type: 'card',
            props: {
              title: 'Right Panel',
            },
            children: [
              {
                id: 'right-text',
                type: 'typography.p',
                props: {
                  text: 'This is the right panel content',
                },
              },
            ],
          },
        ],
      },
    ],
  },
};

export const aspectRatioExample: AspectRatioConfig = {
  id: 'aspect-1',
  type: 'aspect-ratio',
  props: {
    ratio: '16/9',
    className: 'bg-muted rounded-md',
  },
  children: [
    {
      id: 'aspect-content',
      type: 'typography.p',
      props: {
        text: '16:9 Aspect Ratio Container',
        className: 'flex items-center justify-center h-full',
      },
    },
  ],
};

export const collapsibleExample: CollapsibleConfig = {
  id: 'collapsible-1',
  type: 'collapsible',
  props: {
    title: 'Click to expand',
    defaultOpen: false,
    className: 'border rounded-md p-4',
  },
  children: [
    {
      id: 'collapsible-content',
      type: 'typography.p',
      props: {
        text: 'This content can be collapsed and expanded.',
      },
    },
    {
      id: 'collapsible-list',
      type: 'typography.p',
      props: {
        text: 'You can add multiple components here.',
      },
    },
  ],
};

export const accordionExample: AccordionConfig = {
  id: 'accordion-1',
  type: 'accordion',
  props: {
    type: 'single',
    collapsible: true,
    className: 'w-full',
    items: [
      {
        value: 'item-1',
        title: 'Is it accessible?',
        content: [
          {
            id: 'answer-1',
            type: 'typography.p',
            props: {
              text: 'Yes. It adheres to the WAI-ARIA design pattern.',
            },
          },
        ],
      },
      {
        value: 'item-2',
        title: 'Is it styled?',
        content: [
          {
            id: 'answer-2',
            type: 'typography.p',
            props: {
              text: 'Yes. It comes with default styles that matches the other components aesthetic.',
            },
          },
        ],
      },
      {
        value: 'item-3',
        title: 'Is it animated?',
        content: [
          {
            id: 'answer-3',
            type: 'typography.p',
            props: {
              text: 'Yes. It is animated by default, but you can disable it if you prefer.',
            },
          },
        ],
      },
    ],
  },
};

export const accordionMultipleExample: AccordionConfig = {
  id: 'accordion-2',
  type: 'accordion',
  props: {
    type: 'multiple',
    defaultValue: ['feature-1'],
    className: 'w-full',
    items: [
      {
        value: 'feature-1',
        title: 'Feature 1',
        content: [
          {
            id: 'feature-1-content',
            type: 'card',
            props: {
              title: 'Advanced Feature',
              description: 'This accordion supports multiple items open at once',
            },
            children: [
              {
                id: 'feature-1-text',
                type: 'typography.p',
                props: {
                  text: 'You can have multiple sections expanded simultaneously.',
                },
              },
            ],
          },
        ],
      },
      {
        value: 'feature-2',
        title: 'Feature 2',
        content: [
          {
            id: 'feature-2-content',
            type: 'typography.p',
            props: {
              text: 'Second feature content',
            },
          },
        ],
      },
    ],
  },
};

export const layoutExamples = {
  card: cardExample,
  cardWithFooter: cardWithFooterExample,
  scrollArea: scrollAreaExample,
  resizable: resizableExample,
  aspectRatio: aspectRatioExample,
  collapsible: collapsibleExample,
  accordion: accordionExample,
  accordionMultiple: accordionMultipleExample,
};
