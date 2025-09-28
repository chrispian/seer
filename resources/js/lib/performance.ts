import React from 'react';

/**
 * Performance monitoring utilities for tracking critical user flows
 */

interface PerformanceMetric {
  name: string;
  value: number;
  timestamp: number;
  metadata?: Record<string, any>;
}

class PerformanceMonitor {
  private metrics: PerformanceMetric[] = [];
  private startTimes = new Map<string, number>();
  private enabled = process.env.NODE_ENV === 'development' || window.location.search.includes('debug=perf');

  /**
   * Start timing a performance metric
   */
  startTiming(name: string, metadata?: Record<string, any>) {
    if (!this.enabled) return;
    
    this.startTimes.set(name, performance.now());
    
    if (metadata) {
      console.log(`🚀 Starting: ${name}`, metadata);
    }
  }

  /**
   * End timing and record the metric
   */
  endTiming(name: string, metadata?: Record<string, any>) {
    if (!this.enabled) return;
    
    const startTime = this.startTimes.get(name);
    if (!startTime) {
      console.warn(`No start time found for metric: ${name}`);
      return;
    }

    const duration = performance.now() - startTime;
    this.recordMetric(name, duration, metadata);
    this.startTimes.delete(name);
    
    // Log slow operations
    if (duration > 1000) {
      console.warn(`🐌 Slow operation: ${name} took ${duration.toFixed(2)}ms`, metadata);
    } else if (duration > 100) {
      console.log(`⚠️ ${name} took ${duration.toFixed(2)}ms`, metadata);
    } else {
      console.log(`✅ ${name} took ${duration.toFixed(2)}ms`, metadata);
    }
  }

  /**
   * Record a performance metric
   */
  recordMetric(name: string, value: number, metadata?: Record<string, any>) {
    if (!this.enabled) return;
    
    const metric: PerformanceMetric = {
      name,
      value,
      timestamp: Date.now(),
      metadata,
    };

    this.metrics.push(metric);
    
    // Keep only last 100 metrics to avoid memory leaks
    if (this.metrics.length > 100) {
      this.metrics.shift();
    }
  }

  /**
   * Mark a Web Vitals metric
   */
  markVital(name: string, value: number) {
    if (!this.enabled) return;
    
    this.recordMetric(`vital:${name}`, value);
    console.log(`📊 Web Vital - ${name}: ${value.toFixed(2)}`);
  }

  /**
   * Get all recorded metrics
   */
  getMetrics(): PerformanceMetric[] {
    return [...this.metrics];
  }

  /**
   * Get metrics by name pattern
   */
  getMetricsByPattern(pattern: RegExp): PerformanceMetric[] {
    return this.metrics.filter(m => pattern.test(m.name));
  }

  /**
   * Get performance summary
   */
  getSummary() {
    const summary = new Map<string, { count: number; total: number; avg: number; max: number; min: number }>();
    
    this.metrics.forEach(metric => {
      const existing = summary.get(metric.name) || { count: 0, total: 0, avg: 0, max: 0, min: Infinity };
      
      existing.count++;
      existing.total += metric.value;
      existing.avg = existing.total / existing.count;
      existing.max = Math.max(existing.max, metric.value);
      existing.min = Math.min(existing.min, metric.value);
      
      summary.set(metric.name, existing);
    });

    return Object.fromEntries(summary);
  }

  /**
   * Clear all metrics
   */
  clear() {
    this.metrics.length = 0;
    this.startTimes.clear();
  }

  /**
   * Export metrics for analysis
   */
  exportMetrics() {
    return {
      metrics: this.getMetrics(),
      summary: this.getSummary(),
      timestamp: Date.now(),
      userAgent: navigator.userAgent,
      url: window.location.href,
    };
  }
}

// Export singleton instance
export const perfMonitor = new PerformanceMonitor();

// Utility functions for common performance patterns
export const withPerformanceTracking = <T extends (...args: any[]) => any>(
  fn: T,
  name: string,
  metadata?: Record<string, any>
): T => {
  return ((...args: any[]) => {
    perfMonitor.startTiming(name, metadata);
    
    try {
      const result = fn(...args);
      
      // Handle async functions
      if (result && typeof result.then === 'function') {
        return result.finally(() => {
          perfMonitor.endTiming(name, metadata);
        });
      }
      
      perfMonitor.endTiming(name, metadata);
      return result;
    } catch (error) {
      perfMonitor.endTiming(name, { ...metadata, error: error.message });
      throw error;
    }
  }) as T;
};

// React Hook for component performance tracking
export const usePerformanceTracking = (componentName: string) => {
  React.useEffect(() => {
    const mountTime = `${componentName}:mount`;
    perfMonitor.startTiming(mountTime);
    
    return () => {
      perfMonitor.endTiming(mountTime);
    };
  }, [componentName]);
  
  const trackAction = React.useCallback((actionName: string, metadata?: Record<string, any>) => {
    const fullName = `${componentName}:${actionName}`;
    perfMonitor.startTiming(fullName, metadata);
    
    return () => perfMonitor.endTiming(fullName, metadata);
  }, [componentName]);
  
  return { trackAction };
};

// Performance tracking for React Query
export const trackQueryPerformance = (queryKey: any[], operation: 'fetch' | 'mutation') => {
  const name = `query:${operation}:${Array.isArray(queryKey) ? queryKey.join(':') : queryKey}`;
  perfMonitor.startTiming(name);
  
  return () => perfMonitor.endTiming(name);
};

// Critical user flow tracking
export const trackUserFlow = {
  chatSwitch: (sessionId: number) => {
    perfMonitor.startTiming('flow:chat-switch', { sessionId });
    return () => perfMonitor.endTiming('flow:chat-switch', { sessionId });
  },
  
  vaultSwitch: (vaultId: number) => {
    perfMonitor.startTiming('flow:vault-switch', { vaultId });
    return () => perfMonitor.endTiming('flow:vault-switch', { vaultId });
  },
  
  chatCreation: () => {
    perfMonitor.startTiming('flow:chat-creation');
    return () => perfMonitor.endTiming('flow:chat-creation');
  },
  
  messageCompose: () => {
    perfMonitor.startTiming('flow:message-compose');
    return () => perfMonitor.endTiming('flow:message-compose');
  },
};