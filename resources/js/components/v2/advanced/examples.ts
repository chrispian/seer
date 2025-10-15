import { DataTableConfig, ChartConfig, CarouselConfig, SonnerConfig } from '../types';

export const dataTableExample: DataTableConfig = {
  id: 'example-data-table',
  type: 'data-table',
  props: {
    columns: [
      { key: 'id', label: 'ID', sortable: true, width: '80', align: 'center' },
      { 
        key: 'user', 
        label: 'User', 
        sortable: true, 
        render: 'avatar',
      },
      { key: 'email', label: 'Email', sortable: true },
      { 
        key: 'status', 
        label: 'Status', 
        sortable: true, 
        filterable: true,
        render: 'badge',
      },
      { key: 'role', label: 'Role', sortable: true },
      { key: 'lastActive', label: 'Last Active', sortable: true },
    ],
    data: [
      {
        id: 1,
        user: { 
          src: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Alice',
          alt: 'Alice Johnson',
          fallback: 'AJ',
          label: 'Alice Johnson'
        },
        email: 'alice@example.com',
        status: { text: 'Active', variant: 'default' },
        role: 'Admin',
        lastActive: '2 hours ago',
      },
      {
        id: 2,
        user: {
          src: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Bob',
          alt: 'Bob Smith',
          fallback: 'BS',
          label: 'Bob Smith'
        },
        email: 'bob@example.com',
        status: { text: 'Active', variant: 'default' },
        role: 'Editor',
        lastActive: '1 day ago',
      },
      {
        id: 3,
        user: {
          src: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Carol',
          alt: 'Carol White',
          fallback: 'CW',
          label: 'Carol White'
        },
        email: 'carol@example.com',
        status: { text: 'Inactive', variant: 'secondary' },
        role: 'Viewer',
        lastActive: '1 week ago',
      },
      {
        id: 4,
        user: {
          src: 'https://api.dicebear.com/7.x/avataaars/svg?seed=David',
          alt: 'David Brown',
          fallback: 'DB',
          label: 'David Brown'
        },
        email: 'david@example.com',
        status: { text: 'Suspended', variant: 'destructive' },
        role: 'Editor',
        lastActive: '2 weeks ago',
      },
      {
        id: 5,
        user: {
          src: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Emma',
          alt: 'Emma Davis',
          fallback: 'ED',
          label: 'Emma Davis'
        },
        email: 'emma@example.com',
        status: { text: 'Active', variant: 'default' },
        role: 'Admin',
        lastActive: '5 minutes ago',
      },
    ],
    pagination: {
      enabled: true,
      pageSize: 10,
    },
    selection: {
      enabled: true,
      type: 'multiple',
    },
    actions: {
      rowClick: {
        type: 'emit',
        event: 'user:view',
      },
      rowActions: [
        {
          id: 'edit',
          type: 'button',
          props: {
            label: 'Edit',
          },
          actions: {
            click: {
              type: 'emit',
              event: 'user:edit',
            },
          },
        },
        {
          id: 'delete',
          type: 'button',
          props: {
            label: 'Delete',
          },
          actions: {
            click: {
              type: 'emit',
              event: 'user:delete',
            },
          },
        },
      ],
    },
  },
};

export const barChartExample: ChartConfig = {
  id: 'example-bar-chart',
  type: 'chart',
  props: {
    chartType: 'bar',
    title: 'Monthly Revenue',
    data: [
      { label: 'Jan', value: 4000 },
      { label: 'Feb', value: 3000 },
      { label: 'Mar', value: 5000 },
      { label: 'Apr', value: 4500 },
      { label: 'May', value: 6000 },
      { label: 'Jun', value: 5500 },
    ],
    legend: true,
    height: 300,
  },
};

export const lineChartExample: ChartConfig = {
  id: 'example-line-chart',
  type: 'chart',
  props: {
    chartType: 'line',
    title: 'User Growth',
    data: [
      { label: 'Week 1', value: 100 },
      { label: 'Week 2', value: 150 },
      { label: 'Week 3', value: 200 },
      { label: 'Week 4', value: 280 },
      { label: 'Week 5', value: 350 },
      { label: 'Week 6', value: 420 },
    ],
    legend: true,
    height: 300,
  },
};

export const pieChartExample: ChartConfig = {
  id: 'example-pie-chart',
  type: 'chart',
  props: {
    chartType: 'pie',
    title: 'Traffic Sources',
    data: [
      { label: 'Direct', value: 35 },
      { label: 'Search', value: 45 },
      { label: 'Social', value: 15 },
      { label: 'Email', value: 5 },
    ],
    legend: true,
    height: 350,
  },
};

export const donutChartExample: ChartConfig = {
  id: 'example-donut-chart',
  type: 'chart',
  props: {
    chartType: 'donut',
    title: 'Project Status',
    data: [
      { label: 'Completed', value: 45 },
      { label: 'In Progress', value: 30 },
      { label: 'Pending', value: 15 },
      { label: 'Cancelled', value: 10 },
    ],
    legend: true,
    height: 350,
  },
};

export const areaChartExample: ChartConfig = {
  id: 'example-area-chart',
  type: 'chart',
  props: {
    chartType: 'area',
    title: 'Sales Trend',
    data: [
      { label: 'Q1', value: 12000 },
      { label: 'Q2', value: 15000 },
      { label: 'Q3', value: 18000 },
      { label: 'Q4', value: 22000 },
    ],
    legend: true,
    height: 300,
  },
};

export const carouselExample: CarouselConfig = {
  id: 'example-carousel',
  type: 'carousel',
  props: {
    items: [
      {
        id: 'slide-1',
        type: 'card',
        props: {
          title: 'Welcome to UI Builder',
          description: 'Build beautiful interfaces with config-driven components',
          className: 'w-full',
        },
      },
      {
        id: 'slide-2',
        type: 'card',
        props: {
          title: 'Advanced Components',
          description: 'DataTables, Charts, Carousels, and more',
          className: 'w-full',
        },
      },
      {
        id: 'slide-3',
        type: 'card',
        props: {
          title: 'Fully Customizable',
          description: 'Configure every aspect through JSON',
          className: 'w-full',
        },
      },
    ],
    autoplay: true,
    interval: 5000,
    loop: true,
    showDots: true,
    showArrows: true,
  },
};

export const sonnerDefaultExample: SonnerConfig = {
  id: 'example-sonner-default',
  type: 'sonner',
  props: {
    message: 'Event has been created',
    description: 'Monday, January 3rd at 6:00pm',
    duration: 4000,
  },
};

export const sonnerSuccessExample: SonnerConfig = {
  id: 'example-sonner-success',
  type: 'sonner',
  props: {
    message: 'Success!',
    description: 'Your changes have been saved',
    variant: 'success',
    duration: 3000,
  },
};

export const sonnerErrorExample: SonnerConfig = {
  id: 'example-sonner-error',
  type: 'sonner',
  props: {
    message: 'Error',
    description: 'Something went wrong. Please try again.',
    variant: 'error',
    duration: 5000,
  },
};

export const sonnerWithActionExample: SonnerConfig = {
  id: 'example-sonner-action',
  type: 'sonner',
  props: {
    message: 'Undo Available',
    description: 'Item deleted successfully',
    action: {
      label: 'Undo',
      action: {
        type: 'emit',
        event: 'item:restore',
      },
    },
    duration: 6000,
  },
};

export const advancedExamples = {
  dataTable: {
    default: dataTableExample,
  },
  chart: {
    bar: barChartExample,
    line: lineChartExample,
    pie: pieChartExample,
    donut: donutChartExample,
    area: areaChartExample,
  },
  carousel: {
    default: carouselExample,
  },
  sonner: {
    default: sonnerDefaultExample,
    success: sonnerSuccessExample,
    error: sonnerErrorExample,
    withAction: sonnerWithActionExample,
  },
};
