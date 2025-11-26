import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { ArrowLeft, Gift, Save, AlertTriangle } from 'lucide-react';

export default function PrizeEdit({ auth, event, prize, has_entries }) {
    const { data, setData, patch, processing, errors } = useForm({
        name: prize.name || '',
        description: prize.description || '',
        stock: prize.stock || 1,
        active: prize.active !== undefined ? prize.active : true,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        patch(route('events.prizes.update', [event.id, prize.id]));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center">
                    <Link
                        href={route('events.prizes.index', event.id)}
                        className="mr-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                            Editar Premio
                        </h2>
                        <p className="text-sm text-gray-600 dark:text-gray-300 mt-1">
                            {event.name}
                        </p>
                    </div>
                </div>
            }
        >
            <Head title={`Editar Premio - ${event.name}`} />

            <div className="py-6">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    {has_entries && (
                        <div className="mb-6 p-4 border border-orange-200 bg-orange-50 dark:bg-orange-900/20 dark:border-orange-800 rounded-md flex items-start">
                            <AlertTriangle className="h-5 w-5 text-orange-600 dark:text-orange-400 mr-3 mt-0.5 flex-shrink-0" />
                            <p className="text-sm text-orange-800 dark:text-orange-200">
                                Este premio tiene participaciones registradas. No se puede modificar el stock.
                            </p>
                        </div>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center">
                                <Gift className="w-5 h-5 mr-2 text-blue-500" />
                                Información del Premio
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div className="max-w-2xl">
                                    {/* Name */}
                                    <div>
                                        <label htmlFor="name" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Nombre del Premio *
                                        </label>
                                        <input
                                            type="text"
                                            id="name"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400"
                                            placeholder="Ej: iPhone 15 Pro, Laptop Dell, etc."
                                        />
                                        {errors.name && (
                                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.name}</p>
                                        )}
                                    </div>

                                    {/* Description */}
                                    <div>
                                        <label htmlFor="description" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Descripción
                                        </label>
                                        <textarea
                                            id="description"
                                            rows={4}
                                            value={data.description}
                                            onChange={(e) => setData('description', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400"
                                            placeholder="Describe el premio en detalle..."
                                        />
                                        {errors.description && (
                                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.description}</p>
                                        )}
                                    </div>

                                    {/* Stock */}
                                    <div>
                                        <label htmlFor="stock" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Stock Disponible *
                                            {has_entries && (
                                                <span className="ml-2 text-xs text-orange-600 dark:text-orange-400">
                                                    (No editable - tiene participaciones)
                                                </span>
                                            )}
                                        </label>
                                        <input
                                            type="number"
                                            id="stock"
                                            min="1"
                                            max="1000"
                                            value={data.stock}
                                            onChange={(e) => setData('stock', parseInt(e.target.value))}
                                            disabled={has_entries}
                                            className={`w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400 ${
                                                has_entries ? 'opacity-50 cursor-not-allowed' : ''
                                            }`}
                                        />
                                        {errors.stock && (
                                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.stock}</p>
                                        )}
                                    </div>

                                    {/* Active Status */}
                                    <div className="flex items-center">
                                        <input
                                            type="checkbox"
                                            id="active"
                                            checked={data.active}
                                            onChange={(e) => setData('active', e.target.checked)}
                                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded"
                                        />
                                        <label htmlFor="active" className="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                            Premio activo (disponible para rifas)
                                        </label>
                                    </div>
                                </div>

                                {/* Action Buttons */}
                                <div className="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                                    <Link
                                        href={route('events.prizes.index', event.id)}
                                        className="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                                    >
                                        Cancelar
                                    </Link>
                                    
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="inline-flex items-center px-6 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-md"
                                    >
                                        {processing ? (
                                            <>
                                                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                                Guardando...
                                            </>
                                        ) : (
                                            <>
                                                <Save className="w-4 h-4 mr-2" />
                                                Guardar Cambios
                                            </>
                                        )}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

