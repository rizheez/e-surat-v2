import { Button } from '@/Components/ui/button';
import { Command, CommandEmpty, CommandInput, CommandItem, CommandList } from '@/Components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { cn } from '@/lib/utils';
import { Check, ChevronsUpDown, X } from 'lucide-react';
import * as React from 'react';

export type MultiComboboxOption = {
    value: string;
    label: string;
    description?: string | null;
    disabled?: boolean;
};

type MultiComboboxProps = {
    options: MultiComboboxOption[];
    value: string[];
    onChange: (value: string[]) => void;
    placeholder?: string;
    searchPlaceholder?: string;
    emptyText?: string;
    className?: string;
    disabled?: boolean;
};

export function MultiCombobox({
    options,
    value,
    onChange,
    placeholder = 'Pilih data',
    searchPlaceholder = 'Cari data...',
    emptyText = 'Data tidak ditemukan.',
    className,
    disabled = false,
}: MultiComboboxProps) {
    const [open, setOpen] = React.useState(false);

    const selectedOptions = React.useMemo(
        () => options.filter((option) => value.includes(option.value)),
        [options, value],
    );

    function toggle(nextValue: string) {
        if (disabled) {
            return;
        }

        onChange(
            value.includes(nextValue)
                ? value.filter((item) => item !== nextValue)
                : [...value, nextValue],
        );
    }

    function remove(nextValue: string) {
        onChange(value.filter((item) => item !== nextValue));
    }

    return (
        <div className={cn('space-y-3', className)}>
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        type="button"
                        variant="outline"
                        role="combobox"
                        aria-expanded={open}
                        disabled={disabled}
                        className="flex h-auto min-h-10 w-full items-center justify-between gap-3 rounded-md border border-cyan-950/10 bg-white px-3 py-2 text-sm font-normal text-slate-950 hover:bg-white"
                    >
                        <span className={cn('truncate text-left', selectedOptions.length === 0 && 'text-slate-500')}>
                            {selectedOptions.length > 0
                                ? `${selectedOptions.length} penerima dipilih`
                                : placeholder}
                        </span>
                        <ChevronsUpDown className="h-4 w-4 shrink-0 text-slate-400" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-[var(--radix-popover-trigger-width)] p-0">
                    <Command>
                        <CommandInput placeholder={searchPlaceholder} />
                        <CommandList>
                            <CommandEmpty>{emptyText}</CommandEmpty>
                            {options.map((option) => {
                                const checked = value.includes(option.value);

                                return (
                                    <CommandItem
                                        key={option.value}
                                        value={`${option.label} ${option.description ?? ''} ${option.value}`}
                                        onSelect={() => toggle(option.value)}
                                        disabled={option.disabled}
                                        className="items-start py-2.5"
                                    >
                                        <Check className={cn('mt-0.5 h-4 w-4 text-cyan-800', checked ? 'opacity-100' : 'opacity-0')} />
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium text-slate-950">{option.label}</p>
                                            {option.description && (
                                                <p className="mt-0.5 truncate text-xs text-slate-500">{option.description}</p>
                                            )}
                                        </div>
                                    </CommandItem>
                                );
                            })}
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>

            {selectedOptions.length > 0 && (
                <div className="flex flex-wrap gap-2">
                    {selectedOptions.map((option) => (
                        <span
                            key={option.value}
                            className="inline-flex max-w-full items-center gap-2 rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1 text-xs font-medium text-cyan-950"
                        >
                            <span className="truncate">{option.label}</span>
                            <button
                                type="button"
                                onClick={() => remove(option.value)}
                                className="rounded-full p-0.5 text-cyan-700 transition hover:bg-cyan-100 hover:text-cyan-950"
                                aria-label={`Hapus ${option.label}`}
                            >
                                <X className="h-3 w-3" />
                            </button>
                        </span>
                    ))}
                </div>
            )}
        </div>
    );
}
