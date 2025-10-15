import { useForm } from 'react-hook-form';
import { FormConfig } from '../types';
import { renderComponent } from '../ComponentRegistry';

export function FormComponent({ config }: { config: FormConfig }) {
  const { props, actions } = config;
  const {
    fields = [],
    submitButton,
    onSubmit: submitAction,
    className,
  } = props || {};

  const {
    handleSubmit,
    formState: { errors },
  } = useForm();

  const onSubmit = (data: any) => {
    if (actions?.submit || submitAction) {
      const action = actions?.submit || submitAction;
      const { type, command, event: eventName, url, method, payload } = action!;
      
      const submitData = { ...payload, ...data };
      
      if (type === 'command' && command) {
        window.dispatchEvent(new CustomEvent('command:execute', { detail: { command, payload: submitData } }));
      } else if (type === 'emit' && eventName) {
        window.dispatchEvent(new CustomEvent(eventName, { detail: submitData }));
      } else if (type === 'http' && url) {
        fetch(url, {
          method: method || 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(submitData),
        }).catch(console.error);
      }
    }
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className={className}>
      <div className="space-y-4">
        {fields.map((field) => {
          const { name, label, field: fieldComponent, validation, helperText } = field;
          const error = errors[name];

          return (
            <div key={name} className="space-y-2">
              {label && (
                <label htmlFor={name} className="text-sm font-medium">
                  {label}
                  {validation?.required && <span className="text-destructive ml-1">*</span>}
                </label>
              )}
              
              <div>
                {renderComponent(fieldComponent)}
              </div>

              {error && (
                <p className="text-sm text-destructive">{(error as any).message}</p>
              )}
              
              {helperText && !error && (
                <p className="text-sm text-muted-foreground">{helperText}</p>
              )}
            </div>
          );
        })}
      </div>

      {submitButton && (
        <div className="mt-6">
          {renderComponent(submitButton)}
        </div>
      )}
    </form>
  );
}
