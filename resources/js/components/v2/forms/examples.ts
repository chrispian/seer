export const formExamples = {
  form: {
    id: 'form-example',
    type: 'form',
    props: {
      fields: [
        {
          name: 'email',
          label: 'Email',
          field: {
            id: 'email-input',
            type: 'input',
            props: {
              type: 'email',
              placeholder: 'Enter your email',
            },
          },
          validation: {
            required: true,
            pattern: '^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$',
          },
        },
        {
          name: 'password',
          label: 'Password',
          field: {
            id: 'password-input',
            type: 'input',
            props: {
              type: 'password',
              placeholder: 'Enter your password',
            },
          },
          validation: {
            required: true,
            min: 8,
          },
          helperText: 'Must be at least 8 characters',
        },
      ],
      submitButton: {
        id: 'submit-btn',
        type: 'button',
        props: {
          label: 'Submit',
          variant: 'default',
        },
      },
    },
    actions: {
      submit: {
        type: 'emit',
        event: 'form:submit',
      },
    },
  },

  inputGroup: {
    id: 'input-group-example',
    type: 'input-group',
    props: {
      prefix: '$',
      suffix: 'USD',
      input: {
        id: 'amount-input',
        type: 'input',
        props: {
          type: 'number',
          placeholder: '0.00',
        },
      },
    },
  },

  inputGroupWithIcon: {
    id: 'input-group-icon',
    type: 'input-group',
    props: {
      prefix: {
        id: 'search-icon',
        type: 'button.icon',
        props: {
          icon: 'Search',
          variant: 'ghost',
          size: 'sm',
        },
      },
      input: {
        id: 'search-input',
        type: 'input',
        props: {
          placeholder: 'Search...',
        },
      },
    },
  },

  inputOTP: {
    id: 'otp-example',
    type: 'input-otp',
    props: {
      length: 6,
    },
    actions: {
      complete: {
        type: 'emit',
        event: 'otp:verified',
      },
    },
  },

  datePicker: {
    id: 'date-picker-example',
    type: 'date-picker',
    props: {
      placeholder: 'Select a date',
      format: 'PPP',
    },
    actions: {
      change: {
        type: 'emit',
        event: 'date:selected',
      },
    },
  },

  calendar: {
    id: 'calendar-example',
    type: 'calendar',
    props: {
      mode: 'single',
    },
    actions: {
      change: {
        type: 'emit',
        event: 'calendar:change',
      },
    },
  },

  calendarRange: {
    id: 'calendar-range',
    type: 'calendar',
    props: {
      mode: 'range',
    },
    actions: {
      change: {
        type: 'emit',
        event: 'daterange:selected',
      },
    },
  },

  buttonGroup: {
    id: 'button-group-example',
    type: 'button-group',
    props: {
      buttons: [
        { value: 'left', label: 'Left', icon: 'AlignLeft' },
        { value: 'center', label: 'Center', icon: 'AlignCenter' },
        { value: 'right', label: 'Right', icon: 'AlignRight' },
      ],
      value: 'left',
    },
    actions: {
      change: {
        type: 'emit',
        event: 'alignment:changed',
      },
    },
  },

  toggle: {
    id: 'toggle-example',
    type: 'toggle',
    props: {
      icon: 'Bold',
      variant: 'outline',
    },
    actions: {
      change: {
        type: 'emit',
        event: 'bold:toggled',
      },
    },
  },

  toggleWithLabel: {
    id: 'toggle-label',
    type: 'toggle',
    props: {
      label: 'Italic',
      icon: 'Italic',
      pressed: false,
    },
  },

  toggleGroup: {
    id: 'toggle-group-example',
    type: 'toggle-group',
    props: {
      type: 'single',
      items: [
        { value: 'bold', icon: 'Bold' },
        { value: 'italic', icon: 'Italic' },
        { value: 'underline', icon: 'Underline' },
      ],
      variant: 'outline',
    },
    actions: {
      change: {
        type: 'emit',
        event: 'formatting:changed',
      },
    },
  },

  toggleGroupMultiple: {
    id: 'toggle-group-multi',
    type: 'toggle-group',
    props: {
      type: 'multiple',
      items: [
        { value: 'bold', label: 'Bold' },
        { value: 'italic', label: 'Italic' },
        { value: 'strikethrough', label: 'Strikethrough' },
      ],
    },
  },

  item: {
    id: 'item-example',
    type: 'item',
    props: {
      title: 'John Doe',
      description: 'Software Engineer',
      avatar: 'https://i.pravatar.cc/150?img=1',
      badge: 'Online',
    },
    actions: {
      click: {
        type: 'navigate',
        url: '/profile/john-doe',
      },
    },
  },

  itemWithIcon: {
    id: 'item-icon',
    type: 'item',
    props: {
      title: 'Settings',
      description: 'Manage your account',
      icon: 'Settings',
      trailing: {
        id: 'chevron',
        type: 'button.icon',
        props: {
          icon: 'ChevronRight',
          variant: 'ghost',
          size: 'sm',
        },
      },
    },
  },
};

export const formComponentsList = [
  'form',
  'input-group',
  'input-otp',
  'date-picker',
  'calendar',
  'button-group',
  'toggle',
  'toggle-group',
  'item',
];
