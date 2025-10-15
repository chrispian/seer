import { useState, useEffect, useCallback } from 'react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { ComponentConfig } from '../types';
import { renderComponent } from '../ComponentRegistry';

interface CarouselConfig extends ComponentConfig {
  type: 'carousel';
  props: {
    items: ComponentConfig[];
    autoplay?: boolean;
    interval?: number;
    loop?: boolean;
    showDots?: boolean;
    showArrows?: boolean;
    className?: string;
  };
}

export function CarouselComponent({ config }: { config: CarouselConfig }) {
  const { props } = config;
  const {
    items = [],
    autoplay = false,
    interval = 5000,
    loop = true,
    showDots = true,
    showArrows = true,
    className,
  } = props;

  const [currentIndex, setCurrentIndex] = useState(0);
  const [isPaused, setIsPaused] = useState(false);

  const goToSlide = useCallback((index: number) => {
    if (loop) {
      setCurrentIndex((index + items.length) % items.length);
    } else {
      setCurrentIndex(Math.max(0, Math.min(index, items.length - 1)));
    }
  }, [items.length, loop]);

  const goToPrevious = useCallback(() => {
    goToSlide(currentIndex - 1);
  }, [currentIndex, goToSlide]);

  const goToNext = useCallback(() => {
    goToSlide(currentIndex + 1);
  }, [currentIndex, goToSlide]);

  useEffect(() => {
    if (!autoplay || isPaused || items.length <= 1) return;

    const timer = setInterval(() => {
      goToNext();
    }, interval);

    return () => clearInterval(timer);
  }, [autoplay, isPaused, interval, items.length, goToNext]);

  if (!items.length) {
    return (
      <div className={cn('w-full h-64 flex items-center justify-center border rounded-lg', className)}>
        <div className="text-center text-muted-foreground">
          <p>No items to display</p>
        </div>
      </div>
    );
  }

  const canGoPrevious = loop || currentIndex > 0;
  const canGoNext = loop || currentIndex < items.length - 1;

  return (
    <div
      className={cn('relative w-full overflow-hidden rounded-lg', className)}
      onMouseEnter={() => setIsPaused(true)}
      onMouseLeave={() => setIsPaused(false)}
    >
      <div className="relative aspect-video w-full bg-muted">
        <div
          className="flex transition-transform duration-500 ease-out h-full"
          style={{ transform: `translateX(-${currentIndex * 100}%)` }}
        >
          {items.map((item, index) => (
            <div key={`slide-${index}`} className="min-w-full h-full flex items-center justify-center p-6">
              {renderComponent(item)}
            </div>
          ))}
        </div>
      </div>

      {showArrows && items.length > 1 && (
        <>
          <Button
            variant="outline"
            size="icon"
            className="absolute left-2 top-1/2 -translate-y-1/2 h-8 w-8 rounded-full bg-background/80 backdrop-blur-sm"
            onClick={goToPrevious}
            disabled={!canGoPrevious}
            aria-label="Previous slide"
          >
            <ChevronLeft className="h-4 w-4" />
          </Button>
          <Button
            variant="outline"
            size="icon"
            className="absolute right-2 top-1/2 -translate-y-1/2 h-8 w-8 rounded-full bg-background/80 backdrop-blur-sm"
            onClick={goToNext}
            disabled={!canGoNext}
            aria-label="Next slide"
          >
            <ChevronRight className="h-4 w-4" />
          </Button>
        </>
      )}

      {showDots && items.length > 1 && (
        <div className="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
          {items.map((_, index) => (
            <button
              key={`dot-${index}`}
              onClick={() => goToSlide(index)}
              className={cn(
                'h-2 w-2 rounded-full transition-all',
                currentIndex === index
                  ? 'bg-primary w-6'
                  : 'bg-primary/30 hover:bg-primary/50'
              )}
              aria-label={`Go to slide ${index + 1}`}
            />
          ))}
        </div>
      )}

      {autoplay && (
        <div className="absolute top-2 right-2 text-xs text-muted-foreground bg-background/80 backdrop-blur-sm px-2 py-1 rounded">
          {isPaused ? 'Paused' : 'Auto'}
        </div>
      )}
    </div>
  );
}
