import {
  BarChart,
  Bar,
  LineChart,
  Line,
  PieChart,
  Pie,
  AreaChart,
  Area,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
  Cell,
} from 'recharts';
import { cn } from '@/lib/utils';
import { ComponentConfig } from '../types';

interface ChartDataPoint {
  label: string;
  value: number;
  [key: string]: any;
}

interface ChartConfig extends ComponentConfig {
  type: 'chart';
  props: {
    chartType: 'bar' | 'line' | 'pie' | 'area' | 'donut';
    data: ChartDataPoint[];
    title?: string;
    legend?: boolean;
    colors?: string[];
    height?: number;
    xAxisKey?: string;
    yAxisKey?: string;
    showGrid?: boolean;
    showTooltip?: boolean;
    className?: string;
  };
}

const DEFAULT_COLORS = [
  'hsl(var(--chart-1))',
  'hsl(var(--chart-2))',
  'hsl(var(--chart-3))',
  'hsl(var(--chart-4))',
  'hsl(var(--chart-5))',
];

export function ChartComponent({ config }: { config: ChartConfig }) {
  const { props } = config;
  const {
    chartType,
    data = [],
    title,
    legend = true,
    colors = DEFAULT_COLORS,
    height = 350,
    xAxisKey = 'label',
    yAxisKey = 'value',
    showGrid = true,
    showTooltip = true,
    className,
  } = props;

  if (!data.length) {
    return (
      <div className={cn('w-full flex items-center justify-center border rounded-lg', className)} style={{ height }}>
        <div className="text-center text-muted-foreground">
          <p>No data available</p>
        </div>
      </div>
    );
  }

  const renderChart = () => {
    switch (chartType) {
      case 'bar':
        return (
          <ResponsiveContainer width="100%" height={height}>
            <BarChart data={data}>
              {showGrid && <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />}
              <XAxis dataKey={xAxisKey} className="text-xs" />
              <YAxis className="text-xs" />
              {showTooltip && <Tooltip contentStyle={{ background: 'hsl(var(--background))', border: '1px solid hsl(var(--border))' }} />}
              {legend && <Legend />}
              <Bar dataKey={yAxisKey} fill={colors[0]} radius={[4, 4, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        );

      case 'line':
        return (
          <ResponsiveContainer width="100%" height={height}>
            <LineChart data={data}>
              {showGrid && <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />}
              <XAxis dataKey={xAxisKey} className="text-xs" />
              <YAxis className="text-xs" />
              {showTooltip && <Tooltip contentStyle={{ background: 'hsl(var(--background))', border: '1px solid hsl(var(--border))' }} />}
              {legend && <Legend />}
              <Line type="monotone" dataKey={yAxisKey} stroke={colors[0]} strokeWidth={2} dot={{ r: 4 }} />
            </LineChart>
          </ResponsiveContainer>
        );

      case 'area':
        return (
          <ResponsiveContainer width="100%" height={height}>
            <AreaChart data={data}>
              {showGrid && <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />}
              <XAxis dataKey={xAxisKey} className="text-xs" />
              <YAxis className="text-xs" />
              {showTooltip && <Tooltip contentStyle={{ background: 'hsl(var(--background))', border: '1px solid hsl(var(--border))' }} />}
              {legend && <Legend />}
              <Area type="monotone" dataKey={yAxisKey} stroke={colors[0]} fill={colors[0]} fillOpacity={0.3} />
            </AreaChart>
          </ResponsiveContainer>
        );

      case 'pie':
      case 'donut':
        return (
          <ResponsiveContainer width="100%" height={height}>
            <PieChart>
              {showTooltip && <Tooltip contentStyle={{ background: 'hsl(var(--background))', border: '1px solid hsl(var(--border))' }} />}
              {legend && <Legend />}
              <Pie
                data={data}
                dataKey={yAxisKey}
                nameKey={xAxisKey}
                cx="50%"
                cy="50%"
                innerRadius={chartType === 'donut' ? '60%' : 0}
                outerRadius="80%"
                fill={colors[0]}
                label
              >
                {data.map((_, index) => (
                  <Cell key={`cell-${index}`} fill={colors[index % colors.length]} />
                ))}
              </Pie>
            </PieChart>
          </ResponsiveContainer>
        );

      default:
        return (
          <div className="w-full flex items-center justify-center" style={{ height }}>
            <p className="text-muted-foreground">Unsupported chart type: {chartType}</p>
          </div>
        );
    }
  };

  return (
    <div className={cn('space-y-4', className)}>
      {title && <h3 className="text-lg font-semibold">{title}</h3>}
      <div className="w-full">
        {renderChart()}
      </div>
    </div>
  );
}
