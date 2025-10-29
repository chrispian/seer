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
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm();

  const onSubmit = async (data: any) => {
    console.log('Form submitting with data:', data);
    
    if (actions?.submit || submitAction) {
      const action = actions?.submit || submitAction;
      const { type, command, event: eventName, url, method, payload } = action!;
      
      const submitData = { ...payload, ...data };
      
      if (type === 'command' && command) {
        window.dispatchEvent(new CustomEvent('command:execute', { detail: { command, payload: submitData } }));
      } else if (type === 'emit' && eventName) {
        window.dispatchEvent(new CustomEvent(eventName, { detail: submitData }));
      } else if (type === 'http' && url) {
        try {
          const response = await fetch(url, {
            method: method || 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(submitData),
          });
          
          const result = await response.json();
          console.log('Form submission successful:', result);
          
          // Store session_id if returned
          if (result.session_id) {
            sessionStorage.setItem('builder_session_id', result.session_id);
            console.log('Stored session_id:', result.session_id);
          }
          
          // Emit success event
          window.dispatchEvent(new CustomEvent('form:submit:success', {
            detail: { result, formData: submitData }
          }));
        } catch (error) {
          console.error('Form submission error:', error);
          window.dispatchEvent(new CustomEvent('form:submit:error', {
            detail: { error }
          }));
        }
      }
    }
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className={className}>
      <div className="space-y-4">
        {fields.map((field) => {
          const { name, label, field: fieldComponent, validation, helperText } = field;
          const error = errors[name];

          // Convert validation to react-hook-form format
          const registerOptions: any = {};
          if (validation?.required) {
            registerOptions.required = 'This field is required';
          }
          if (validation?.min !== undefined) {
            registerOptions.min = { value: validation.min, message: `Minimum value is ${validation.min}` };
          }
          if (validation?.max !== undefined) {
            registerOptions.max = { value: validation.max, message: `Maximum value is ${validation.max}` };
          }
          if (validation?.pattern) {
            registerOptions.pattern = { value: new RegExp(validation.pattern), message: 'Invalid format' };
          }

          // Clone field component and inject register
          const enhancedFieldComponent = {
            ...fieldComponent,
            props: {
              ...fieldComponent.props,
              ...register(name, registerOptions),
            },
          };

          return (
            <div key={name} className="space-y-2">
              {label && (
                <label htmlFor={name} className="text-sm font-medium">
                  {label}
                  {validation?.required && <span className="text-destructive ml-1">*</span>}
                </label>
              )}
              
              <div>
                {renderComponent(enhancedFieldComponent)}
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
          {renderComponent({
            ...submitButton,
            props: {
              ...submitButton.props,
              type: 'submit',
              disabled: isSubmitting,
              loading: isSubmitting,
            },
          })}
        </div>
      )}
    </form>
  );
}
