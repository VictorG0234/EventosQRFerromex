import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    EnvelopeIcon,
    UserGroupIcon,
    ChartBarIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon
} from '@heroicons/react/24/outline';

const CONFIRM_WORD = 'envio-masivo';

export default function Index({ event, statistics, guests, auth }) {
    const [activeTab, setActiveTab] = useState('overview');
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState(null);
    const [confirmText, setConfirmText] = useState('');

    const sendBulkWelcome = () => {
        if (confirmText !== CONFIRM_WORD) return;
        setLoading(true);
        router.post(route('events.emails.bulk-welcome', event.id), { confirmation: confirmText }, {
            onSuccess: () => {
                setConfirmText('');
                setMessage({ type: 'success', text: 'Emails de bienvenida enviados exitosamente' });
                setLoading(false);
            },
            onError: () => {
                setMessage({ type: 'error', text: 'Error al enviar emails de bienvenida' });
                setLoading(false);
            }
        });
    };

    const tabs = [
        { id: 'overview', name: 'Resumen', icon: ChartBarIcon },
        { id: 'bulk', name: 'Envíos Masivos', icon: UserGroupIcon }
    ];

    const emailStats = [
        {
            name: 'Total de Invitados',
            stat: statistics.total_guests,
            icon: UserGroupIcon,
            color: 'bg-blue-500'
        },
        {
            name: 'Con Email',
            stat: statistics.guests_with_email,
            icon: EnvelopeIcon,
            color: 'bg-green-500'
        },
        {
            name: 'Sin Email',
            stat: statistics.guests_without_email,
            icon: ExclamationTriangleIcon,
            color: 'bg-orange-500'
        },
        {
            name: 'Cobertura de Email',
            stat: `${statistics.email_coverage_percentage}%`,
            icon: ChartBarIcon,
            color: 'bg-purple-500'
        }
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                            📧 Sistema de Emails - {event.name}
                        </h2>
                        <p className="text-gray-600 dark:text-gray-300 mt-1">
                            Gestiona todas las comunicaciones por email de tu evento
                        </p>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Link
                            href={route('events.invitation.edit', event.id)}
                            className="inline-flex items-center rounded-md bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800"
                        >
                            Personalizar invitación
                        </Link>
                        <Link
                            href={route('events.show', event.id)}
                            className="inline-flex items-center rounded-md bg-gray-600 px-3 py-2 text-sm font-medium text-white hover:bg-gray-700"
                        >
                            ← Volver al evento
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`Emails - ${event.name}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Messages */}
                    {message && (
                        <div className={`mb-4 p-4 rounded-md ${
                            message.type === 'success' 
                                ? 'bg-green-50 text-green-700 border border-green-200'
                                : 'bg-red-50 text-red-700 border border-red-200'
                        }`}>
                            <div className="flex items-center">
                                {message.type === 'success' ? (
                                    <CheckCircleIcon className="h-5 w-5 mr-2" />
                                ) : (
                                    <ExclamationTriangleIcon className="h-5 w-5 mr-2" />
                                )}
                                {message.text}
                            </div>
                        </div>
                    )}

                    {/* Statistics Grid */}
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                        {emailStats.map((item) => (
                            <div
                                key={item.name}
                                className="relative bg-white pt-5 px-4 pb-12 sm:pt-6 sm:px-6 shadow rounded-lg overflow-hidden"
                            >
                                <dt>
                                    <div className={`absolute rounded-md p-3 ${item.color}`}>
                                        <item.icon className="h-6 w-6 text-white" aria-hidden="true" />
                                    </div>
                                    <p className="ml-16 text-sm font-medium text-gray-500 truncate">{item.name}</p>
                                </dt>
                                <dd className="ml-16 pb-6 flex items-baseline sm:pb-7">
                                    <p className="text-2xl font-semibold text-gray-900">{item.stat}</p>
                                </dd>
                            </div>
                        ))}
                    </div>

                    {/* Tabs */}
                    <div className="bg-white shadow rounded-lg">
                        <div className="border-b border-gray-200">
                            <nav className="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                                {tabs.map((tab) => (
                                    <button
                                        key={tab.id}
                                        onClick={() => setActiveTab(tab.id)}
                                        className={`${
                                            activeTab === tab.id
                                                ? 'border-blue-500 text-blue-600'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                        } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center space-x-2`}
                                    >
                                        <tab.icon className="h-5 w-5" />
                                        <span>{tab.name}</span>
                                    </button>
                                ))}
                            </nav>
                        </div>

                        <div className="px-6 py-8">
                            {activeTab === 'overview' && (
                                <div className="space-y-6">
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900 mb-4">
                                            📊 Resumen de Comunicaciones
                                        </h3>
                                        
                                        {statistics.email_coverage_percentage < 50 && (
                                            <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-4">
                                                <div className="flex">
                                                    <ExclamationTriangleIcon className="h-5 w-5 text-yellow-400" />
                                                    <div className="ml-3">
                                                        <h4 className="text-sm font-medium text-yellow-800">
                                                            Cobertura de Email Baja
                                                        </h4>
                                                        <div className="mt-2 text-sm text-yellow-700">
                                                            Solo {statistics.email_coverage_percentage}% de tus invitados tienen email. 
                                                            Considera recopilar más emails para mejorar la comunicación.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        )}

                                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div className="bg-gray-50 p-4 rounded-lg">
                                                <h4 className="font-medium text-gray-900 mb-2">Invitados sin Email</h4>
                                                <p className="text-sm text-gray-600">
                                                    {statistics.guests_without_email} invitados no recibirán notificaciones automáticas.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {activeTab === 'bulk' && (
                                <div className="space-y-6">
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900 mb-4">
                                            📨 Envíos Masivos
                                        </h3>
                                        
                                        <div className="border border-gray-200 rounded-lg p-6 max-w-xl">
                                            <h4 className="font-medium text-gray-900 mb-2">Emails de Bienvenida</h4>
                                            <p className="text-sm text-gray-600 mb-4">
                                                Envía la invitación con código QR a todos los invitados con email.
                                            </p>
                                            <label className="block text-sm font-medium text-gray-700">
                                                Para confirmar, escribe <span className="font-mono font-bold">{CONFIRM_WORD}</span>
                                            </label>
                                            <input
                                                type="text"
                                                value={confirmText}
                                                onChange={(e) => setConfirmText(e.target.value)}
                                                autoComplete="off"
                                                className="mt-1 mb-4 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                                placeholder={CONFIRM_WORD}
                                            />
                                            <button
                                                onClick={sendBulkWelcome}
                                                disabled={loading || statistics.guests_with_email === 0 || confirmText !== CONFIRM_WORD}
                                                className="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                <EnvelopeIcon className="h-4 w-4 mr-2" />
                                                Enviar a {statistics.guests_with_email} invitados
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}