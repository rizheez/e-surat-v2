import { Button } from '@/Components/ui/button';
import { Command, CommandEmpty, CommandInput, CommandItem, CommandList } from '@/Components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { cn } from '@/lib/utils';
import { Check, ChevronsUpDown } from 'lucide-react';
import * as React from 'react';

type NativeSelectProps = Omit<React.SelectHTMLAttributes<HTMLSelectElement>, 'children'> & {
    children?: React.ReactNode;
};

type ParsedOption = {
    value: string;
    label: string;
    disabled: boolean;
};

const Select = React.forwardRef<HTMLSelectElement, NativeSelectProps>(
    ({ className, children, value, defaultValue, onChange, disabled, name, required, ...props }, ref) => {
        const [open, setOpen] = React.useState(false);
        const options = React.useMemo(() => collectOptions(children), [children]);
        const currentValue = value ?? defaultValue ?? '';
        const normalizedValue = typeof currentValue === 'string' ? currentValue : String(currentValue ?? '');
        const selectedOption = options.find((option) => option.value === normalizedValue) ?? null;
        const placeholderOption = options.find((option) => option.value === '');
        const triggerLabel = selectedOption?.label ?? placeholderOption?.label ?? 'Pilih data';

        function handleSelect(nextValue: string) {
            if (disabled) {
                return;
            }

            const syntheticEvent = {
                target: { value: nextValue, name },
                currentTarget: { value: nextValue, name },
            } as React.ChangeEvent<HTMLSelectElement>;

            onChange?.(syntheticEvent);
            setOpen(false);
        }

        return (
            <div className="relative">
                <select
                    ref={ref}
                    value={normalizedValue}
                    onChange={onChange}
                    disabled={disabled}
                    name={name}
                    required={required}
                    className="sr-only"
                    tabIndex={-1}
                    aria-hidden="true"
                    {...props}
                >
                    {children}
                </select>

                <Popover open={open} onOpenChange={setOpen}>
                    <PopoverTrigger asChild>
                        <Button
                            type="button"
                            variant="outline"
                            role="combobox"
                            aria-expanded={open}
                            disabled={disabled}
                            className={cn(
                                'flex h-10 w-full items-center justify-between rounded-md border border-cyan-950/10 bg-white px-3 py-2 text-sm font-normal text-slate-950 ring-offset-white hover:bg-white focus-visible:ring-cyan-800',
                                !selectedOption && 'text-slate-500',
                                className,
                            )}
                        >
                            <span className="truncate text-left">{triggerLabel}</span>
                            <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 text-slate-400" />
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent className="w-[var(--radix-popover-trigger-width)] p-0">
                        <Command>
                            <CommandInput placeholder="Cari pilihan..." />
                            <CommandList>
                                <CommandEmpty>Pilihan tidak ditemukan.</CommandEmpty>
                                {options.map((option) => (
                                    <CommandItem
                                        key={`${name ?? 'select'}-${option.value || 'empty'}`}
                                        value={`${option.label} ${option.value}`}
                                        onSelect={() => handleSelect(option.value)}
                                        disabled={option.disabled}
                                    >
                                        <Check
                                            className={cn(
                                                'h-4 w-4 text-cyan-800',
                                                normalizedValue === option.value ? 'opacity-100' : 'opacity-0',
                                            )}
                                        />
                                        <span className="truncate">{option.label}</span>
                                    </CommandItem>
                                ))}
                            </CommandList>
                        </Command>
                    </PopoverContent>
                </Popover>
            </div>
        );
    },
);
Select.displayName = 'Select';

function collectOptions(children: React.ReactNode): ParsedOption[] {
    return React.Children.toArray(children).flatMap((child) => {
        if (!React.isValidElement(child)) {
            return [];
        }

        if (child.type === React.Fragment) {
            return collectOptions(child.props.children);
        }

        if (typeof child.type === 'string' && child.type.toLowerCase() === 'option') {
            return [
                {
                    value: String(child.props.value ?? ''),
                    label: readOptionLabel(child.props.children),
                    disabled: Boolean(child.props.disabled),
                },
            ];
        }

        return [];
    });
}

function readOptionLabel(children: React.ReactNode): string {
    return React.Children.toArray(children)
        .map((child) => {
            if (typeof child === 'string' || typeof child === 'number') {
                return String(child);
            }

            if (React.isValidElement(child)) {
                return readOptionLabel(child.props.children);
            }

            return '';
        })
        .join(' ')
        .replace(/\s+/g, ' ')
        .trim();
}

export { Select };
