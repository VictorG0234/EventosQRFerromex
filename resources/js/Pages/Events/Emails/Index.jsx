import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    EnvelopeIcon,
    UserGroupIcon,
    ClockIcon,
    ChatBubbleLeftRightIcon,
    ChartBarIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    EyeIcon,
    PaperAirplaneIcon,
    Cog8ToothIcon
} from '@heroicons/react/24/outline';

export default function Index({ event, statistics, guests, auth }) {
    const [activeTab, setActiveTab] = useState('overview');
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState(null);

    // Form for sending reminder
    const reminderForm = useForm({
        hours_before_event: 24
    });

    // Form for custom message
    const customMessageForm = useForm({
        subject: '',
        message: '',
        guest_ids: []
    });

    const sendReminder = () => {
        setLoading(true);
        reminderForm.post(route('events.emails.reminder', event.id), {
            onSuccess: () => {
                setMessage({ type: 'success', text: 'Recordatorio enviado exitosamente' });
                setLoading(false);
            },
            onError: () => {
                setMessage({ type: 'error', text: 'Error al enviar recordatorio' });
                setLoading(false);
            }
        });
    };

    const sendBulkWelcome = () => {
        setLoading(true);
        router.post(route('events.emails.bulk-welcome', event.id), {}, {
            onSuccess: () => {
                setMessage({ type: 'success', text: 'Emails de bienvenida enviados exitosamente' });
                setLoading(false);
            },
            onError: () => {
                setMessage({ type: 'error', text: 'Error al enviar emails de bienvenida' });
                setLoading(false);
            }
        });
    };

    const sendCustomMessage = () => {
        setLoading(true);
        customMessageForm.post(route('events.emails.custom-message', event.id), {
            onSuccess: () => {
                setMessage({ type: 'success', text: 'Mensaje personalizado enviado exitosamente' });
                customMessageForm.reset();
                setLoading(false);
            },
            onError: () => {
                setMessage({ type: 'error', text: 'Error al enviar mensaje personalizado' });
                setLoading(false);
            }
        });
    };

    const sendEventSummary = () => {
        setLoading(true);
        router.post(route('events.emails.summary', event.id), {}, {
            onSuccess: () => {
                setMessage({ type: 'success', text: 'Resumen del evento enviado exitosamente' });
                setLoading(false);
            },
            onError: () => {
                setMessage({ type: 'error', text: 'Error al enviar resumen del evento' });
                setLoading(false);
            }
        });
    };

    const tabs = [
        { id: 'overview', name: 'Resumen', icon: ChartBarIcon },
        { id: 'bulk', name: 'Envíos Masivos', icon: UserGroupIcon },
        { id: 'custom', name: 'Mensaje Personalizado', icon: ChatBubbleLeftRightIcon },
        { id: 'templates', name: 'Plantillas', icon: EyeIcon }
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

    const quickActions = [
        {
            title: 'Enviar Bienvenida',
            description: 'Envía emails de bienvenida a todos los invitados con email',
            action: sendBulkWelcome,
            icon: EnvelopeIcon,
            color: 'bg-blue-600',
            enabled: statistics.guests_with_email > 0
        },
        {
            title: 'Recordatorio 24h',
            description: 'Envía recordatorio del evento 24 horas antes',
            action: () => {
                reminderForm.setValue('hours_before_event', 24);
                sendReminder();
            },
            icon: ClockIcon,
            color: 'bg-orange-600',
            enabled: statistics.guests_with_email > 0
        },
        {
            title: 'Recordatorio 2h',
            description: 'Envía recordatorio urgente 2 horas antes',
            action: () => {
                reminderForm.setValue('hours_before_event', 2);
                sendReminder();
            },
            icon: ClockIcon,
            color: 'bg-red-600',
            enabled: statistics.guests_with_email > 0
        },
        {
            title: 'Resumen Final',
            description: 'Envía resumen del evento al organizador',
            action: sendEventSummary,
            icon: ChartBarIcon,
            color: 'bg-purple-600',
            enabled: true
        }
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            📧 Sistema de Emails - {event.name}
                        </h2>
                        <p className="text-gray-600 mt-1">
                            Gestiona todas las comunicaciones por email de tu evento
                        </p>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Cog8ToothIcon className="h-5 w-5 text-gray-400" />
                        <span className="text-sm text-gray-500">Sistema Automático</span>
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

                    {/* Quick Actions */}
                    <div className="bg-white shadow rounded-lg mb-8">
                        <div className="px-6 py-4 border-b border-gray-200">
                            <h3 className="text-lg leading-6 font-medium text-gray-900">
                                🚀 Acciones Rápidas
                            </h3>
                            <p className="mt-1 text-sm text-gray-500">
                                Ejecuta las acciones más comunes con un solo clic
                            </p>
                        </div>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 p-6">
                            {quickActions.map((action, index) => (
                                <button
                                    key={index}
                                    onClick={action.action}
                                    disabled={!action.enabled || loading}
                                    className={`relative group bg-white p-6 rounded-lg border-2 border-dashed border-gray-300 hover:border-gray-400 transition-colors ${
                                        !action.enabled || loading ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'
                                    }`}
                                >
                                    <div>
                                        <span className={`rounded-lg inline-flex p-3 ${action.color} text-white`}>
                                            <action.icon className="h-6 w-6" aria-hidden="true" />
                                        </span>
                                    </div>
                                    <div className="mt-4">
                                        <h3 className="text-lg font-medium text-gray-900">
                                            {action.title}
                                        </h3>
                                        <p className="mt-2 text-sm text-gray-500">
                                            {action.description}
                                        </p>
                                    </div>
                                </button>
                            ))}
                        </div>
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
                                            <div className="bg-blue-50 p-4 rounded-lg">
                                                <h4 className="font-medium text-blue-900 mb-2">Próximas Acciones</h4>
                                                <p className="text-sm text-blue-700">
                                                    Programa recordatorios automáticos y mensajes personalizados.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {activeTab === 'custom' && (
                                <div className="space-y-6">
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900 mb-4">
                                            ✍️ Mensaje Personalizado
                                        </h3>
                                        
                                        <div className="space-y-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700">
                                                    Asunto del Email
                                                </label>
                                                <input
                                                    type="text"
                                                    value={customMessageForm.data.subject}
                                                    onChange={(e) => customMessageForm.setData('subject', e.target.value)}
                                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="Escribe el asunto de tu mensaje..."
                                                />
                                                {customMessageForm.errors.subject && (
                                                    <p className="mt-2 text-sm text-red-600">{customMessageForm.errors.subject}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700">
                                                    Mensaje
                                                </label>
                                                <textarea
                                                    rows={6}
                                                    value={customMessageForm.data.message}
                                                    onChange={(e) => customMessageForm.setData('message', e.target.value)}
                                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="Escribe tu mensaje personalizado aquí..."
                                                />
                                                {customMessageForm.errors.message && (
                                                    <p className="mt-2 text-sm text-red-600">{customMessageForm.errors.message}</p>
                                                )}
                                            </div>

                                            <div className="flex justify-between items-center">
                                                <p className="text-sm text-gray-500">
                                                    Se enviará a {statistics.guests_with_email} invitados con email
                                                </p>
                                                <button
                                                    type="button"
                                                    onClick={sendCustomMessage}
                                                    disabled={loading || !customMessageForm.data.subject || !customMessageForm.data.message}
                                                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                                >
                                                    <PaperAirplaneIcon className="h-4 w-4 mr-2" />
                                                    {loading ? 'Enviando...' : 'Enviar Mensaje'}
                                                </button>
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
                                        
                                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                            <div className="border border-gray-200 rounded-lg p-6">
                                                <h4 className="font-medium text-gray-900 mb-2">Emails de Bienvenida</h4>
                                                <p className="text-sm text-gray-600 mb-4">
                                                    Envía emails de bienvenida con códigos QR a todos los invitados.
                                                </p>
                                                <button
                                                    onClick={sendBulkWelcome}
                                                    disabled={loading || statistics.guests_with_email === 0}
                                                    className="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 disabled:opacity-50"
                                                >
                                                    <EnvelopeIcon className="h-4 w-4 mr-2" />
                                                    Enviar a {statistics.guests_with_email} invitados
                                                </button>
                                            </div>

                                            <div className="border border-gray-200 rounded-lg p-6">
                                                <h4 className="font-medium text-gray-900 mb-2">Recordatorios</h4>
                                                <p className="text-sm text-gray-600 mb-4">
                                                    Programa recordatorios automáticos del evento.
                                                </p>
                                                <div className="space-y-2">
                                                    <button
                                                        onClick={() => {
                                                            reminderForm.setData('hours_before_event', 24);
                                                            sendReminder();
                                                        }}
                                                        disabled={loading || statistics.guests_with_email === 0}
                                                        className="w-full inline-flex justify-center items-center px-3 py-2 border border-orange-200 text-sm font-medium rounded text-orange-700 bg-orange-50 hover:bg-orange-100 disabled:opacity-50"
                                                    >
                                                        <ClockIcon className="h-4 w-4 mr-2" />
                                                        24 horas antes
                                                    </button>
                                                    <button
                                                        onClick={() => {
                                                            reminderForm.setData('hours_before_event', 2);
                                                            sendReminder();
                                                        }}
                                                        disabled={loading || statistics.guests_with_email === 0}
                                                        className="w-full inline-flex justify-center items-center px-3 py-2 border border-red-200 text-sm font-medium rounded text-red-700 bg-red-50 hover:bg-red-100 disabled:opacity-50"
                                                    >
                                                        <ClockIcon className="h-4 w-4 mr-2" />
                                                        2 horas antes
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {activeTab === 'templates' && (
                                <div className="space-y-6">
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900 mb-4">
                                            🎨 Plantillas de Email
                                        </h3>
                                        <p className="text-sm text-gray-600 mb-6">
                                            Vista previa de las plantillas de email disponibles en el sistema.
                                        </p>
                                        
                                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                            {[
                                                { name: 'Bienvenida', id: 'guest-welcome', description: 'Email de bienvenida con código QR' },
                                                { name: 'Recordatorio', id: 'event-reminder', description: 'Recordatorio antes del evento' },
                                                { name: 'Confirmación', id: 'attendance-confirmation', description: 'Confirmación de asistencia' },
                                                { name: 'Resumen', id: 'event-summary', description: 'Resumen final del evento' },
                                                { name: 'Personalizado', id: 'custom-message', description: 'Mensaje personalizado' },
                                                { name: 'Ganador Rifa', id: 'raffle-winner', description: 'Notificación de ganador' }
                                            ].map((template) => (
                                                <div key={template.id} className="border border-gray-200 rounded-lg p-4">
                                                    <h4 className="font-medium text-gray-900 mb-2">{template.name}</h4>
                                                    <p className="text-sm text-gray-600 mb-3">{template.description}</p>
                                                    <button
                                                        onClick={() => {
                                                            window.open(`/events/${event.id}/emails/preview?template=${template.id}`, '_blank');
                                                        }}
                                                        className="text-sm text-blue-600 hover:text-blue-800 flex items-center"
                                                    >
                                                        <EyeIcon className="h-4 w-4 mr-1" />
                                                        Vista Previa
                                                    </button>
                                                </div>
                                            ))}
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