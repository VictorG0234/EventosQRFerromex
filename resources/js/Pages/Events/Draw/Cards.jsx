import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import RaffleModal from '@/Components/RaffleModal';
import { Gift, Sparkles, ArrowLeft, RotateCcw, Trophy } from 'lucide-react';
import { toast } from 'react-hot-toast';

export default function DrawCards({ auth, event, prizes }) {
    const { flash } = usePage().props;
    const [selectedPrizeCard, setSelectedPrizeCard] = useState(null);

    useEffect(() => {
        if (flash.success) {
            toast.success(flash.success);
        }
        if (flash.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    const handleOpenRaffleModal = (prizeCard) => {
        setSelectedPrizeCard(prizeCard);
    };

    const handleCloseModal = () => {
        setSelectedPrizeCard(null);
    };

    const handleWinnerSelected = () => {
        // Recargar la página cuando hay un ganador para actualizar las tarjetas
        // Forzar recarga sin caché para asegurar datos actualizados
        window.location.reload(true);
    };

    // Expandir premios en tarjetas individuales según su stock
    const prizeCards = [];
    prizes.forEach((prize) => {
        const winnersForThisPrize = prize.winners || [];
        
        // El stock siempre es 1 según el usuario
        // Crear solo 1 tarjeta por premio
        const totalSlots = 1;
        
        // Crear una tarjeta por cada unidad de stock original
        for (let i = 0; i < totalSlots; i++) {
            const isWon = i < winnersForThisPrize.length;
            const winnerInfo = isWon ? winnersForThisPrize[i] : null;
            
            // Permitir rifar si:
            // 1. La tarjeta ya tiene ganador (para volver a rifar)
            // 2. O si hay asistentes elegibles y el premio está activo
            const canRaffle = isWon || (prize.eligible_count > 0 && prize.is_available);
            
            prizeCards.push({
                id: `${prize.id}-${i}`,
                prizeId: prize.id,
                prizeName: prize.name,
                index: i + 1,
                isWon: isWon,
                winner: winnerInfo,
                canRaffle: canRaffle,
                stock: prize.stock,
                winnersCount: prize.winners_count,
                remainingStock: prize.remaining_stock,
                eligibleCount: prize.eligible_count,
                isAvailable: prize.is_available
            });
        }
    });


    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <div className="flex items-center">
                        <Link
                            href={route('events.raffle.index', event.id)}
                            className="mr-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                                Rifa Pública - {event.name}
                            </h2>
                            <p className="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                Selecciona un premio para realizar la rifa
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Link
                            href={route('events.draw.general', event.id)}
                            className="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 dark:bg-orange-500 dark:hover:bg-orange-600 text-white text-sm font-medium rounded-md transition-colors"
                        >
                            <Trophy className="w-4 h-4 mr-2" />
                            Ir a Rifa General
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`Rifa Pública - ${event.name}`}>
                <link rel="preload" href="/fonts/vintage-rotter.woff2" as="font" type="font/woff2" crossOrigin="anonymous" />
            </Head>

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {prizeCards.length === 0 ? (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-8 text-center">
                                <Gift className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 mb-2">No hay premios disponibles</h3>
                                <p className="text-gray-500 mb-6">
                                    Crea algunos premios para comenzar con las rifas.
                                </p>
                                <Link
                                    href={route('events.prizes.create', event.id)}
                                    className="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md"
                                >
                                    <Gift className="w-4 h-4 mr-2" />
                                    Crear Primer Premio
                                </Link>
                            </div>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            {prizeCards.map((card) => (
                                <div
                                    key={card.id}
                                    className="bg-white overflow-hidden shadow-sm sm:rounded-lg border-2 transition-all hover:shadow-lg flex flex-col"
                                    style={{
                                        borderColor: '#D22730',
                                        backgroundColor: card.isWon ? '#FEE2E2' : '#ffffff'
                                    }}
                                >
                                    <div className="p-6 flex flex-col flex-grow">
                                        <div className="mb-4 text-center">
                                            <h3 className="font-semibold text-lg text-gray-900 mb-1">
                                                🎁 {card.prizeName}
                                            </h3>
                                            <p className="text-sm text-gray-500">
                                                {card.stock > 1 ? `Unidad ${card.index} de ${card.stock}` : ''}
                                            </p>
                                        </div>

                                        <div className="">
                                            {card.isWon && card.winner ? (
                                                <div className="p-3 rounded-md" style={{ backgroundColor: '#FEE2E2' }}>
                                                    <p className="text-sm font-medium mb-1" style={{ color: '#991B1B' }}>
                                                        Ganador:
                                                    </p>
                                                    <p className="text-base font-bold" style={{ color: '#7F1D1D' }}>
                                                        {card.winner.company} - {card.winner.employee_number}
                                                    </p>
                                                    <p className="text-base font-bold" style={{ color: '#7F1D1D' }}>
                                                        {card.winner.name}
                                                    </p>
                                                    {card.winner.drawn_at && (
                                                        <p className="text-xs mt-1" style={{ color: '#DC2626' }}>
                                                            {card.winner.drawn_at}
                                                        </p>
                                                    )}
                                                </div>
                                            ) : null}

                                            {!card.canRaffle && !card.isWon && (
                                                <div className="mb-3 p-2 bg-yellow-50 border border-yellow-200 rounded-md">
                                                    <p className="text-xs text-yellow-800">
                                                        {!card.isAvailable 
                                                            ? 'Premio no está activo'
                                                            : card.eligibleCount === 0 
                                                                ? 'No hay asistentes elegibles para este premio'
                                                                : 'No se puede rifar en este momento'}
                                                    </p>
                                                </div>
                                            )}
                                        </div>

                                        <div className="">
                                            {
                                                card.isWon ? null : (
                                                    <Button
                                                        onClick={() => handleOpenRaffleModal(card)}
                                                        disabled={!card.canRaffle}
                                                        className="w-full text-white disabled:cursor-not-allowed"
                                                        style={{
                                                            backgroundColor: card.canRaffle ? '#D22730' : '#d1d5db',
                                                            opacity: card.canRaffle ? 1 : 0.6
                                                        }}
                                                    >
                                                        <Sparkles className="w-4 h-4 mr-2" />
                                                        Rifar
                                                    </Button>
                                                )
                                            }
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    {/* Modal de Rifa */}
                    <RaffleModal
                        isOpen={selectedPrizeCard !== null}
                        onClose={handleCloseModal}
                        selectedPrizeCard={selectedPrizeCard}
                        event={event}
                        onWinnerSelected={handleWinnerSelected}
                    />
                    
                    {/* Precarga del modal completo - Renderiza todos los elementos ocultos para forzar carga de estilos */}
                    <div 
                        style={{ 
                            position: 'absolute', 
                            left: '-9999px', 
                            top: '-9999px',
                            width: '1px', 
                            height: '1px',
                            overflow: 'hidden',
                            opacity: 0,
                            pointerEvents: 'none',
                            visibility: 'hidden'
                        }}
                        aria-hidden="true"
                    >
                        {/* Precargar estructura del modal con todas las clases CSS */}
                        <div className="raffle-modal">
                            <div className="raffle-modal-content"></div>
                            <div className="raffle-modal-inner">
                                <button className="raffle-modal-close">✕</button>
                                <div className="raffle-title">
                                    <img src="/images/logo-gm.png" alt="GrupoMéxico" className="raffle-logo" />
                                </div>
                                <p className="raffle-subtitle">Sorteo de Fin de Año</p>
                                <div className="raffle-employee-number-box">
                                    <p className="raffle-employee-number-label">GM</p>
                                    <p className="raffle-employee-number-large">000000</p>
                                </div>
                                <div className="raffle-employee-number-box">
                                    <p className="raffle-employee-number-animated">GM</p>
                                    <p className="raffle-employee-number-animated">000000</p>
                                </div>
                                <div className="raffle-prize-box">
                                    <p className="raffle-prize-text">PREMIO</p>
                                </div>
                                <button className="raffle-start-button">Iniciar Rifa</button>
                                <p className="raffle-winner-text">¡Ganador!</p>
                                <p className="raffle-winner-name">Nombre del Ganador</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

