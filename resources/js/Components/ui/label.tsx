import { cn } from '@/lib/utils';
import * as React from 'react';

const Label = React.forwardRef<HTMLLabelElement, React.LabelHTMLAttributes<HTMLLabelElement>>(
    ({ className, ...props }, ref) => (
        <label
            ref={ref}
            className={cn('text-sm font-medium leading-none text-cyan-950', className)}
            {...props}
        />
    ),
);
Label.displayName = 'Label';

export { Label };
