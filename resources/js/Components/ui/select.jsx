import React from 'react';
import { cn } from '../../lib/utils';

const SelectContext = React.createContext({});

const Select = ({ children, value, onValueChange, defaultValue }) => {
    const [internalValue, setInternalValue] = React.useState(defaultValue || '');
    const [isOpen, setIsOpen] = React.useState(false);
    const currentValue = value !== undefined ? value : internalValue;

    const handleValueChange = (newValue) => {
        if (onValueChange) {
            onValueChange(newValue);
        } else {
            setInternalValue(newValue);
        }
        setIsOpen(false);
    };

    return (
        <SelectContext.Provider value={{ 
            value: currentValue, 
            onValueChange: handleValueChange,
            isOpen,
            setIsOpen
        }}>
            <div className="relative">
                {children}
            </div>
        </SelectContext.Provider>
    );
};

const SelectTrigger = React.forwardRef(({ className, children, ...props }, ref) => {
    const { isOpen, setIsOpen } = React.useContext(SelectContext);
    
    return (
        <button
            ref={ref}
            type="button"
            className={cn(
                "flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50",
                className
            )}
            onClick={() => setIsOpen(!isOpen)}
            {...props}
        >
            {children}
            <svg
                className={cn("h-4 w-4 opacity-50 transition-transform", isOpen && "rotate-180")}
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
            >
                <polyline points="6,9 12,15 18,9" />
            </svg>
        </button>
    );
});
SelectTrigger.displayName = "SelectTrigger";

const SelectValue = ({ placeholder, className, ...props }) => {
    const { value } = React.useContext(SelectContext);
    
    return (
        <span className={cn("block truncate", className)} {...props}>
            {value || placeholder}
        </span>
    );
};

const SelectContent = React.forwardRef(({ className, children, ...props }, ref) => {
    const { isOpen } = React.useContext(SelectContext);
    
    if (!isOpen) return null;
    
    return (
        <div
            ref={ref}
            className={cn(
                "absolute top-full z-50 mt-1 w-full rounded-md border bg-popover p-1 text-popover-foreground shadow-md animate-in fade-in-0 zoom-in-95",
                className
            )}
            {...props}
        >
            {children}
        </div>
    );
});
SelectContent.displayName = "SelectContent";

const SelectItem = React.forwardRef(({ className, children, value, ...props }, ref) => {
    const { onValueChange, value: selectedValue } = React.useContext(SelectContext);
    const isSelected = selectedValue === value;
    
    return (
        <div
            ref={ref}
            className={cn(
                "relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none focus:bg-accent focus:text-accent-foreground hover:bg-accent",
                isSelected && "bg-accent",
                className
            )}
            onClick={() => onValueChange(value)}
            {...props}
        >
            {isSelected && (
                <span className="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
                    <svg
                        className="h-4 w-4"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    >
                        <polyline points="20,6 9,17 4,12" />
                    </svg>
                </span>
            )}
            <span className="block truncate">{children}</span>
        </div>
    );
});
SelectItem.displayName = "SelectItem";

const SelectLabel = React.forwardRef(({ className, ...props }, ref) => (
    <div
        ref={ref}
        className={cn("py-1.5 pl-8 pr-2 text-sm font-semibold", className)}
        {...props}
    />
));
SelectLabel.displayName = "SelectLabel";

const SelectSeparator = React.forwardRef(({ className, ...props }, ref) => (
    <div
        ref={ref}
        className={cn("-mx-1 my-1 h-px bg-muted", className)}
        {...props}
    />
));
SelectSeparator.displayName = "SelectSeparator";

export {
    Select,
    SelectTrigger,
    SelectValue,
    SelectContent,
    SelectItem,
    SelectLabel,
    SelectSeparator,
};