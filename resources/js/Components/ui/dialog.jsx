import React from 'react';
import { cn } from '../../lib/utils';

const Dialog = ({ children, open, onOpenChange }) => {
    if (!open) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
            <div 
                className="fixed inset-0 bg-black/70" 
                onClick={() => onOpenChange && onOpenChange(false)}
            />
            <div className="relative z-10 w-full h-full">
                {children}
            </div>
        </div>
    );
};

const DialogTrigger = React.forwardRef(({ className, children, ...props }, ref) => {
    return (
        <button
            ref={ref}
            className={className}
            {...props}
        >
            {children}
        </button>
    );
});
DialogTrigger.displayName = "DialogTrigger";

const DialogContent = React.forwardRef(({ className, children, hideClose = false, ...props }, ref) => {
    const hasFullScreenClass = className && (className.includes('w-screen') || className.includes('h-screen') || className.includes('raffle-modal'));
    
    return (
        <div
            ref={ref}
            className={cn(
                hasFullScreenClass 
                    ? "relative z-50 grid w-full h-full scale-100 gap-4 bg-background shadow-lg duration-200"
                    : "relative z-50 grid w-full max-w-lg scale-100 gap-4 border bg-background p-6 shadow-lg duration-200 sm:rounded-lg",
                !hasFullScreenClass && "animate-in fade-in-0 zoom-in-95 slide-in-from-left-1/2 slide-in-from-top-[48%]",
                className
            )}
            {...props}
        >
            {children}
            {!hasFullScreenClass && !hideClose && (
                <button
                    className="absolute right-4 top-4 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none"
                    onClick={() => props.onClose && props.onClose()}
                >
                    <span className="sr-only">Close</span>
                    ✕
                </button>
            )}
        </div>
    );
});
DialogContent.displayName = "DialogContent";

const DialogHeader = ({ className, ...props }) => (
    <div
        className={cn("flex flex-col space-y-1.5 text-center sm:text-left", className)}
        {...props}
    />
);
DialogHeader.displayName = "DialogHeader";

const DialogFooter = ({ className, ...props }) => (
    <div
        className={cn("flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2", className)}
        {...props}
    />
);
DialogFooter.displayName = "DialogFooter";

const DialogTitle = React.forwardRef(({ className, ...props }, ref) => (
    <h2
        ref={ref}
        className={cn("text-lg font-semibold leading-none tracking-tight", className)}
        {...props}
    />
));
DialogTitle.displayName = "DialogTitle";

const DialogDescription = React.forwardRef(({ className, ...props }, ref) => (
    <p
        ref={ref}
        className={cn("text-sm text-muted-foreground", className)}
        {...props}
    />
));
DialogDescription.displayName = "DialogDescription";

export {
    Dialog,
    DialogTrigger,
    DialogContent,
    DialogHeader,
    DialogFooter,
    DialogTitle,
    DialogDescription,
};