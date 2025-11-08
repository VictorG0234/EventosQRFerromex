import React from 'react';
import { cn } from '../../lib/utils';

const buttonVariants = {
    default: "bg-blue-600 text-white hover:bg-blue-700",
    destructive: "bg-red-600 text-white hover:bg-red-700",
    outline: "border border-gray-300 bg-white text-gray-700 hover:bg-gray-50",
    secondary: "bg-gray-200 text-gray-900 hover:bg-gray-300",
    ghost: "hover:bg-gray-100 text-gray-900",
    link: "text-blue-600 underline-offset-4 hover:underline",
};

const buttonSizes = {
    default: "h-10 px-4 py-2",
    sm: "h-9 rounded-md px-3",
    lg: "h-11 rounded-md px-8",
    icon: "h-10 w-10",
};

const Button = React.forwardRef(({ 
    className, 
    variant = "default", 
    size = "default", 
    asChild = false,
    ...props 
}, ref) => {
    const baseClasses = "inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50";
    
    const classes = cn(
        baseClasses,
        buttonVariants[variant],
        buttonSizes[size],
        className
    );

    if (asChild) {
        return React.cloneElement(props.children, { className: classes, ref, ...props });
    }

    return (
        <button
            className={classes}
            ref={ref}
            {...props}
        />
    );
});

Button.displayName = "Button";

export { Button, buttonVariants };