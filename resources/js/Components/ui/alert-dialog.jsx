import React from 'react';
import { cn } from '../../lib/utils';
import { Button } from './button';

const AlertDialog = ({ children, open, onOpenChange }) => {
    if (!open) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div 
                className="fixed inset-0 bg-black/50" 
                onClick={() => onOpenChange && onOpenChange(false)}
            />
            <div className="relative z-10">
                {children}
            </div>
        </div>
    );
};

const AlertDialogTrigger = React.forwardRef(({ className, children, ...props }, ref) => {
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
AlertDialogTrigger.displayName = "AlertDialogTrigger";

const AlertDialogContent = React.forwardRef(({ className, ...props }, ref) => (
    <div
        ref={ref}
        className={cn(
            "relative z-50 grid w-full max-w-lg scale-100 gap-4 border bg-white dark:bg-gray-800 p-6 shadow-lg sm:rounded-lg",
            className
        )}
        {...props}
    />
));
AlertDialogContent.displayName = "AlertDialogContent";

const AlertDialogHeader = ({ className, ...props }) => (
    <div
        className={cn("flex flex-col space-y-2 text-center sm:text-left", className)}
        {...props}
    />
);
AlertDialogHeader.displayName = "AlertDialogHeader";

const AlertDialogFooter = ({ className, ...props }) => (
    <div
        className={cn("flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2", className)}
        {...props}
    />
);
AlertDialogFooter.displayName = "AlertDialogFooter";

const AlertDialogTitle = React.forwardRef(({ className, ...props }, ref) => (
    <h2
        ref={ref}
        className={cn("text-lg font-semibold text-gray-900 dark:text-gray-100", className)}
        {...props}
    />
));
AlertDialogTitle.displayName = "AlertDialogTitle";

const AlertDialogDescription = React.forwardRef(({ className, ...props }, ref) => (
    <p
        ref={ref}
        className={cn("text-sm text-gray-600 dark:text-gray-300", className)}
        {...props}
    />
));
AlertDialogDescription.displayName = "AlertDialogDescription";

const AlertDialogAction = React.forwardRef(({ className, ...props }, ref) => (
    <Button
        ref={ref}
        className={cn("", className)}
        {...props}
    />
));
AlertDialogAction.displayName = "AlertDialogAction";

const AlertDialogCancel = React.forwardRef(({ className, ...props }, ref) => (
    <Button
        variant="outline"
        ref={ref}
        className={cn("mt-2 sm:mt-0", className)}
        {...props}
    />
));
AlertDialogCancel.displayName = "AlertDialogCancel";

export {
    AlertDialog,
    AlertDialogTrigger,
    AlertDialogContent,
    AlertDialogHeader,
    AlertDialogFooter,
    AlertDialogTitle,
    AlertDialogDescription,
    AlertDialogAction,
    AlertDialogCancel,
};