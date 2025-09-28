import React, { Component, ReactNode } from 'react';
import { AlertTriangle, RefreshCw, Home, Bug } from 'lucide-react';
import { Button } from './button';
import { Card, CardContent, CardHeader, CardTitle } from './card';

interface ErrorBoundaryProps {
  children: ReactNode;
  fallback?: ReactNode;
  onError?: (error: Error, errorInfo: React.ErrorInfo) => void;
  showResetButton?: boolean;
  resetKeys?: Array<string | number>;
  resetOnPropsChange?: boolean;
  context?: 'chat' | 'sidebar' | 'main' | 'dialog';
}

interface ErrorBoundaryState {
  hasError: boolean;
  error?: Error;
  errorInfo?: React.ErrorInfo;
  errorId: string;
}

export class ErrorBoundary extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
  private resetTimeoutId: number | null = null;

  constructor(props: ErrorBoundaryProps) {
    super(props);
    this.state = { 
      hasError: false,
      errorId: this.generateErrorId(),
    };
  }

  static getDerivedStateFromError(error: Error): Partial<ErrorBoundaryState> {
    return { 
      hasError: true, 
      error,
      errorId: Date.now().toString(36) + Math.random().toString(36).substr(2),
    };
  }

  componentDidUpdate(prevProps: ErrorBoundaryProps) {
    const { resetKeys, resetOnPropsChange } = this.props;
    const { hasError } = this.state;
    
    // Reset error boundary when resetKeys change
    if (hasError && resetKeys && prevProps.resetKeys) {
      const hasResetKeyChanged = resetKeys.some(
        (resetKey, idx) => prevProps.resetKeys![idx] !== resetKey
      );
      if (hasResetKeyChanged) {
        this.resetErrorBoundary();
      }
    }
    
    // Reset when any props change (if enabled)
    if (hasError && resetOnPropsChange && prevProps !== this.props) {
      this.resetErrorBoundary();
    }
  }

  componentDidCatch(error: Error, errorInfo: React.ErrorInfo) {
    console.error('ErrorBoundary caught an error:', error, errorInfo);
    
    // Store error info for better debugging
    this.setState({ errorInfo });
    
    // Report to error tracking service (if available)
    this.reportError(error, errorInfo);
    
    this.props.onError?.(error, errorInfo);
  }

  generateErrorId = (): string => {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
  };

  reportError = (error: Error, errorInfo: React.ErrorInfo) => {
    // Here you could send to an error tracking service like Sentry
    const errorReport = {
      errorId: this.state.errorId,
      message: error.message,
      stack: error.stack,
      componentStack: errorInfo.componentStack,
      context: this.props.context || 'unknown',
      timestamp: new Date().toISOString(),
      userAgent: navigator.userAgent,
      url: window.location.href,
    };
    
    console.error('Error Report:', errorReport);
    
    // Example: Send to error tracking service
    // errorTrackingService.captureException(error, { extra: errorReport });
  };

  handleRetry = () => {
    this.resetErrorBoundary();
  };

  resetErrorBoundary = () => {
    if (this.resetTimeoutId) {
      clearTimeout(this.resetTimeoutId);
    }
    
    this.setState({ 
      hasError: false, 
      error: undefined,
      errorInfo: undefined,
      errorId: this.generateErrorId(),
    });
  };

  handleClearCache = () => {
    // Clear React Query cache and local storage
    try {
      localStorage.removeItem('app-store');
      if ('caches' in window) {
        caches.keys().then(names => {
          names.forEach(name => caches.delete(name));
        });
      }
      window.location.reload();
    } catch (error) {
      console.error('Failed to clear cache:', error);
      window.location.reload();
    }
  };

  handleGoHome = () => {
    window.location.href = '/';
  };

  getContextualMessage = (): { title: string; description: string } => {
    const { context } = this.props;
    
    switch (context) {
      case 'chat':
        return {
          title: 'Chat Error',
          description: 'There was an issue loading the chat. Your messages are safe and this usually resolves by switching to another chat and back.',
        };
      case 'sidebar':
        return {
          title: 'Sidebar Error', 
          description: 'The sidebar encountered an error. Try refreshing to reload your vaults and projects.',
        };
      case 'dialog':
        return {
          title: 'Dialog Error',
          description: 'The dialog encountered an error. You can close it and try again.',
        };
      default:
        return {
          title: 'Something went wrong',
          description: 'An unexpected error occurred. This is usually temporary and can be resolved by trying again.',
        };
    }
  };

  render() {
    if (this.state.hasError) {
      if (this.props.fallback) {
        return this.props.fallback;
      }

      const { title, description } = this.getContextualMessage();
      const isProductionBuild = process.env.NODE_ENV === 'production';

      return (
        <Card className="m-4 border-red-200 dark:border-red-800">
          <CardHeader>
            <CardTitle className="flex items-center text-red-600 dark:text-red-400">
              <AlertTriangle className="w-5 h-5 mr-2" />
              {title}
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <p className="text-sm text-gray-600 dark:text-gray-300">
              {description}
            </p>
            
            {!isProductionBuild && this.state.error && (
              <details className="mb-4">
                <summary className="text-xs cursor-pointer text-gray-500 hover:text-gray-700">
                  <Bug className="w-3 h-3 inline mr-1" />
                  Technical details (dev mode)
                </summary>
                <div className="mt-2 space-y-2">
                  <div>
                    <div className="text-xs font-medium text-gray-600">Error ID:</div>
                    <code className="text-xs bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">
                      {this.state.errorId}
                    </code>
                  </div>
                  <div>
                    <div className="text-xs font-medium text-gray-600">Message:</div>
                    <pre className="text-xs bg-gray-100 dark:bg-gray-800 p-2 rounded mt-1 overflow-auto max-h-32">
                      {this.state.error.message}
                    </pre>
                  </div>
                  {this.state.error.stack && (
                    <div>
                      <div className="text-xs font-medium text-gray-600">Stack:</div>
                      <pre className="text-xs bg-gray-100 dark:bg-gray-800 p-2 rounded mt-1 overflow-auto max-h-32">
                        {this.state.error.stack}
                      </pre>
                    </div>
                  )}
                </div>
              </details>
            )}
            
            <div className="flex flex-wrap gap-2">
              <Button onClick={this.handleRetry} size="sm">
                <RefreshCw className="w-4 h-4 mr-2" />
                Try Again
              </Button>
              
              {this.props.context !== 'main' && (
                <Button variant="outline" size="sm" onClick={this.handleGoHome}>
                  <Home className="w-4 h-4 mr-2" />
                  Go Home
                </Button>
              )}
              
              <Button
                variant="outline"
                size="sm"
                onClick={() => window.location.reload()}
              >
                <RefreshCw className="w-4 h-4 mr-2" />
                Refresh Page
              </Button>
              
              <Button
                variant="outline"
                size="sm"
                onClick={this.handleClearCache}
                className="text-orange-600 border-orange-200 hover:bg-orange-50"
              >
                Clear Cache & Reload
              </Button>
            </div>
          </CardContent>
        </Card>
      );
    }

    return this.props.children;
  }
}