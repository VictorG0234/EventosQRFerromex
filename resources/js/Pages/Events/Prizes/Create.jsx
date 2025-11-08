import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { ArrowLeft, Gift, Save, X } from 'lucide-react';

export default function PrizeCreate({ auth, event, categories }) {
    const [selectedImage, setSelectedImage] = useState(null);
    const [imagePreview, setImagePreview] = useState(null);
    
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        category: '',
        stock: 1,
        value: '',
        image: null,
        active: true
    });

    const handleImageChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setSelectedImage(file);
            setData('image', file);
            
            // Create preview
            const reader = new FileReader();
            reader.onloadend = () => {
                setImagePreview(reader.result);
            };
            reader.readAsDataURL(file);
        }
    };

    const removeImage = () => {
        setSelectedImage(null);
        setImagePreview(null);
        setData('image', null);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('events.prizes.store', event.id));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center">
                    <Link
                        href={route('events.prizes.index', event.id)}
                        className="mr-4 text-gray-500 hover:text-gray-700"
                    >
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Crear Nuevo Premio
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            {event.name}
                        </p>
                    </div>
                </div>
            }
        >
            <Head title="Crear Premio" />

            <div className="py-6">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center">
                                <Gift className="w-5 h-5 mr-2 text-blue-500" />
                                Información del Premio
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    {/* Left Column - Form Fields */}
                                    <div className="space-y-6">
                                        {/* Name */}
                                        <div>
                                            <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-2">
                                                Nombre del Premio *
                                            </label>
                                            <input
                                                type="text"
                                                id="name"
                                                value={data.name}
                                                onChange={(e) => setData('name', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Ej: iPhone 15 Pro, Laptop Dell, etc."
                                            />
                                            {errors.name && (
                                                <p className="mt-1 text-sm text-red-600">{errors.name}</p>
                                            )}
                                        </div>

                                        {/* Category */}
                                        <div>
                                            <label htmlFor="category" className="block text-sm font-medium text-gray-700 mb-2">
                                                Categoría *
                                            </label>
                                            {categories.length > 0 ? (
                                                <select
                                                    id="category"
                                                    value={data.category}
                                                    onChange={(e) => setData('category', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                                >
                                                    <option value="">Seleccionar categoría...</option>
                                                    {categories.map((category) => (
                                                        <option key={category} value={category}>{category}</option>
                                                    ))}
                                                </select>
                                            ) : (
                                                <input
                                                    type="text"
                                                    id="category"
                                                    value={data.category}
                                                    onChange={(e) => setData('category', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="Ej: Electrónicos, Hogar, Viajes, etc."
                                                />
                                            )}
                                            {errors.category && (
                                                <p className="mt-1 text-sm text-red-600">{errors.category}</p>
                                            )}
                                            <p className="mt-1 text-xs text-gray-500">
                                                Los invitados elegibles se determinarán por esta categoría
                                            </p>
                                        </div>

                                        {/* Description */}
                                        <div>
                                            <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-2">
                                                Descripción
                                            </label>
                                            <textarea
                                                id="description"
                                                rows={4}
                                                value={data.description}
                                                onChange={(e) => setData('description', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Describe el premio en detalle..."
                                            />
                                            {errors.description && (
                                                <p className="mt-1 text-sm text-red-600">{errors.description}</p>
                                            )}
                                        </div>

                                        {/* Stock and Value */}
                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <label htmlFor="stock" className="block text-sm font-medium text-gray-700 mb-2">
                                                    Stock Disponible *
                                                </label>
                                                <input
                                                    type="number"
                                                    id="stock"
                                                    min="1"
                                                    max="1000"
                                                    value={data.stock}
                                                    onChange={(e) => setData('stock', parseInt(e.target.value))}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                                />
                                                {errors.stock && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.stock}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label htmlFor="value" className="block text-sm font-medium text-gray-700 mb-2">
                                                    Valor Estimado ($)
                                                </label>
                                                <input
                                                    type="number"
                                                    id="value"
                                                    min="0"
                                                    step="0.01"
                                                    value={data.value}
                                                    onChange={(e) => setData('value', e.target.value)}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="0.00"
                                                />
                                                {errors.value && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.value}</p>
                                                )}
                                            </div>
                                        </div>

                                        {/* Active Status */}
                                        <div className="flex items-center">
                                            <input
                                                type="checkbox"
                                                id="active"
                                                checked={data.active}
                                                onChange={(e) => setData('active', e.target.checked)}
                                                className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                            />
                                            <label htmlFor="active" className="ml-2 block text-sm text-gray-700">
                                                Premio activo (disponible para rifas)
                                            </label>
                                        </div>
                                    </div>

                                    {/* Right Column - Image Upload */}
                                    <div className="space-y-6">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                                Imagen del Premio
                                            </label>
                                            
                                            {imagePreview ? (
                                                <div className="relative">
                                                    <img
                                                        src={imagePreview}
                                                        alt="Preview"
                                                        className="w-full h-64 object-cover rounded-lg border-2 border-gray-300"
                                                    />
                                                    <button
                                                        type="button"
                                                        onClick={removeImage}
                                                        className="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full p-1"
                                                    >
                                                        <X className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            ) : (
                                                <div className="border-2 border-dashed border-gray-300 rounded-lg p-6">
                                                    <div className="text-center">
                                                        <Gift className="mx-auto h-12 w-12 text-gray-400" />
                                                        <div className="mt-2">
                                                            <label
                                                                htmlFor="image"
                                                                className="cursor-pointer bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                                                            >
                                                                Subir Imagen
                                                            </label>
                                                            <input
                                                                id="image"
                                                                name="image"
                                                                type="file"
                                                                accept="image/*"
                                                                onChange={handleImageChange}
                                                                className="sr-only"
                                                            />
                                                        </div>
                                                        <p className="mt-1 text-xs text-gray-500">
                                                            PNG, JPG, GIF hasta 2MB
                                                        </p>
                                                    </div>
                                                </div>
                                            )}
                                            
                                            {errors.image && (
                                                <p className="mt-1 text-sm text-red-600">{errors.image}</p>
                                            )}
                                        </div>

                                        {/* Info Panel */}
                                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                            <h4 className="font-medium text-blue-900 mb-2">💡 Información importante</h4>
                                            <ul className="text-sm text-blue-800 space-y-1">
                                                <li>• Solo los invitados con asistencia confirmada podrán participar</li>
                                                <li>• La categoría determina qué invitados son elegibles</li>
                                                <li>• El stock se reducirá automáticamente con cada ganador</li>
                                                <li>• Los premios inactivos no aparecerán en las rifas</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                {/* Action Buttons */}
                                <div className="flex items-center justify-between pt-6 border-t border-gray-200">
                                    <Link
                                        href={route('events.prizes.index', event.id)}
                                        className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Cancelar
                                    </Link>
                                    
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="inline-flex items-center px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md"
                                    >
                                        {processing ? (
                                            <>
                                                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                                Guardando...
                                            </>
                                        ) : (
                                            <>
                                                <Save className="w-4 h-4 mr-2" />
                                                Crear Premio
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