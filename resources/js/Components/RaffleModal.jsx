import React, { useState, useEffect, useRef } from 'react';
import { Dialog, DialogContent } from '@/Components/ui/dialog';
import { toast } from 'react-hot-toast';

export default function RaffleModal({ 
    isOpen, 
    onClose, 
    selectedPrizeCard, 
    event,
    onWinnerSelected 
}) {
    const [attendees, setAttendees] = useState([]);
    const [currentNameIndex, setCurrentNameIndex] = useState(0);
    const [winner, setWinner] = useState(null);
    const [isAnimating, setIsAnimating] = useState(false);
    const [isRaffling, setIsRaffling] = useState(false);
    const [isLoading, setIsLoading] = useState(true);
    const animationIntervalRef = useRef(null);
    const autoStartedRef = useRef(false);

    // Cargar asistentes cuando se abre el modal
    useEffect(() => {
        if (selectedPrizeCard && !isRaffling && !isAnimating && isOpen) {
            loadAttendees();
        }
    }, [selectedPrizeCard, isRaffling, isAnimating, isOpen]);

    // Iniciar automáticamente la rifa cuando termine de cargar
    useEffect(() => {
        if (!isLoading && attendees.length > 0 && !isRaffling && !isAnimating && !winner && isOpen && !autoStartedRef.current) {
            autoStartedRef.current = true;
            // Delay para que se complete la transición del logo y título antes de iniciar la animación
            setTimeout(() => {
                startRaffleAnimation();
            }, 600);
        }
    }, [isLoading, attendees.length, isRaffling, isAnimating, winner, isOpen]);

    // Limpiar intervalo cuando el componente se desmonte
    useEffect(() => {
        return () => {
            if (animationIntervalRef.current) {
                cancelAnimationFrame(animationIntervalRef.current);
            }
        };
    }, []);

    // Resetear estados cuando se cierra el modal
    useEffect(() => {
        if (!isOpen) {
            setWinner(null);
            setIsAnimating(false);
            setCurrentNameIndex(0);
            setIsRaffling(false);
            setIsLoading(true);
            autoStartedRef.current = false;
            if (animationIntervalRef.current) {
                cancelAnimationFrame(animationIntervalRef.current);
            }
        }
    }, [isOpen]);

    const loadAttendees = async () => {
        try {
            setIsLoading(true);
            const response = await fetch(route('events.raffle.attendees', event.id));
            const data = await response.json();
            setAttendees(data.attendees || []);
            setIsLoading(false);
        } catch (error) {
            toast.error('Error al cargar la lista de asistentes');
            setIsLoading(false);
        }
    };

    const startRaffleAnimation = async () => {
        if (attendees.length === 0) {
            toast.error('No hay asistentes disponibles para la rifa');
            return;
        }

        setIsAnimating(true);
        setWinner(null);
        // Iniciar con un índice aleatorio en lugar de 0 para que se vea movimiento inmediato
        setCurrentNameIndex(Math.floor(Math.random() * attendees.length));
        setIsRaffling(true);

        // Iniciar la animación inmediatamente mientras se hace el fetch en paralelo
        // Usar una variable mutable para el índice del ganador que se actualizará cuando llegue el resultado
        let finalWinnerIndex = 0;
        let raffleResult = null;
        let winnerReceived = false;

        // Iniciar la animación de nombres aleatorios inmediatamente
        // Si el premio es "Automovil" (o variaciones), duplicar la duración
        const isAutomovil = selectedPrizeCard?.prizeName && 
            selectedPrizeCard.prizeName.toLowerCase().includes('automovil');
        const totalDuration = isAutomovil ? 20000 : 10000; // 20 segundos para Automovil, 10 segundos para otros
        const constantSpeed = 35; // ms entre cambios (velocidad rápida constante)
        const slowDownStart = 0.8; // Empezar a desacelerar al 80%
        const startTime = Date.now();
        let lastUpdateTime = startTime - constantSpeed;

        // Función para cambiar el nombre (extraída para reutilizar)
        const changeName = (currentProgress) => {
            // Si aún no tenemos el ganador, mostrar nombres aleatorios
            if (!winnerReceived) {
                const randomIndex = Math.floor(Math.random() * attendees.length);
                setCurrentNameIndex(randomIndex);
                return;
            }

            // En los últimos 20% del tiempo, empezar a acercarse al ganador
            if (currentProgress > slowDownStart) {                
                // Si estamos cerca del ganador, aumentar probabilidad de mostrar el ganador
                const slowDownProgress = (currentProgress - slowDownStart) / (1 - slowDownStart);
                if (Math.random() < 0.3 + slowDownProgress * 3.5) {
                    // Mostrar el ganador directamente
                    setCurrentNameIndex(finalWinnerIndex);
                } else {
                    // Mostrar nombres cercanos al ganador
                    const range = Math.max(2, Math.floor(attendees.length * 0.1));
                    const offset = Math.floor(Math.random() * range * 2) - range;
                    const targetIndex = (finalWinnerIndex + offset + attendees.length) % attendees.length;
                    setCurrentNameIndex(targetIndex);
                }
            } else {
                // Antes del 80%, mostrar nombres aleatorios
                const randomIndex = Math.floor(Math.random() * attendees.length);
                setCurrentNameIndex(randomIndex);
            }
        };

        // Hacer el primer cambio inmediatamente usando setTimeout para forzar ejecución
        setTimeout(() => {
            changeName(0);
        }, 0);

        const animate = () => {
            const now = Date.now();
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / totalDuration, 1); // Asegurar que no exceda 1

            // Calcular velocidad: constante hasta el 80%, luego desaceleración progresiva
            let speed;
            if (progress < slowDownStart) {
                // Primera parte (0-80%): velocidad constante rápida
                speed = constantSpeed;
            } else {
                // Última parte (80-100%): desaceleración dramática
                const phaseProgress = (progress - slowDownStart) / (1 - slowDownStart);
                // De velocidad constante a muy lento al final
                speed = constantSpeed + (phaseProgress * phaseProgress * 500); // Aceleración cuadrática para desaceleración más dramática
            }

            // Si ha pasado el tiempo necesario según la velocidad actual, cambiar nombre
            // O si es el primer frame (lastUpdateTime está en el pasado), cambiar inmediatamente
            if (now - lastUpdateTime >= speed || lastUpdateTime < startTime) {
                changeName(progress);
                lastUpdateTime = now;
            }

            // Después de 10 segundos, mostrar el ganador definitivo
            if (progress >= 1) {
                cancelAnimationFrame(animationIntervalRef.current);
                // Asegurar que se muestra el ganador correcto
                setCurrentNameIndex(finalWinnerIndex);
                setTimeout(() => {
                    // Buscar el ganador completo en la lista de attendees para obtener todos los datos (incluyendo compania)
                    const fullWinnerData = attendees.find(a => a.id === raffleResult.winner.id) || raffleResult.winner;
                    setWinner(fullWinnerData);
                    setIsAnimating(false);
                    
                    // Lanzar confetti con la librería tsparticles (modo fireworks)
                    if (window.confetti) {
                        const count = 300;
                        const defaults = {
                            colors: ['#ed2d36'],
                            shapes: ['square'],
                            
                        };

                        function fire(particleRatio, opts) {
                            window.confetti({
                                ...defaults,
                                ...opts,
                                particleCount: Math.floor(count * particleRatio)
                            });
                        }

                        fire(0.25, {
                            spread: 260,
                            startVelocity: 55,
                            scalar: 2.4
                        });
                        fire(0.2, {
                            spread: 600,
                            scalar: 2.7
                        });
                        fire(0.35, {
                            spread: 1000,
                            decay: 0.91,
                            scalar: 2
                        });
                        fire(0.1, {
                            spread: 1200,
                            startVelocity: 25,
                            decay: 0.92,
                            scalar: 3.2
                        });
                        fire(0.1, {
                            spread: 1200,
                            startVelocity: 45,
                            scalar: 1.5
                        });
                    }
                    // No llamar onWinnerSelected aquí, solo cuando el usuario cierre el modal
                }, 300); // Esperar un momento antes de mostrar el ganador
            } else {
                animationIntervalRef.current = requestAnimationFrame(animate);
            }
        };

        // Iniciar la animación con requestAnimationFrame para mejor rendimiento
        animationIntervalRef.current = requestAnimationFrame(animate);

        // Hacer el fetch en paralelo mientras la animación ya está corriendo
        try {
            const response = await fetch(route('events.raffle.draw-single', [event.id, selectedPrizeCard.prizeId]), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    card_index: selectedPrizeCard.index - 1,
                    reset_previous: selectedPrizeCard.isWon
                })
            });

            // Verificar si la respuesta es exitosa
            if (!response.ok) {
                // Intentar parsear el error como JSON
                let errorData;
                try {
                    errorData = await response.json();
                } catch (e) {
                    errorData = { message: `Error ${response.status}: ${response.statusText}` };
                }
                
                setIsAnimating(false);
                setIsRaffling(false);
                if (animationIntervalRef.current) {
                    cancelAnimationFrame(animationIntervalRef.current);
                }
                toast.error(errorData.message || errorData.error || `Error ${response.status}: ${response.statusText}`);
                return;
            }

            raffleResult = await response.json();

            if (!raffleResult.success) {
                setIsAnimating(false);
                setIsRaffling(false);
                if (animationIntervalRef.current) {
                    cancelAnimationFrame(animationIntervalRef.current);
                }
                toast.error(raffleResult.message || raffleResult.error || 'Error al realizar la rifa');
                return;
            }

            // Encontrar el índice del ganador en la lista de asistentes
            const winnerIndex = attendees.findIndex(a => a.id === raffleResult.winner.id);
            finalWinnerIndex = winnerIndex >= 0 ? winnerIndex : 0;
            winnerReceived = true; // Marcar que ya tenemos el ganador
        } catch (error) {
            setIsAnimating(false);
            setIsRaffling(false);
            if (animationIntervalRef.current) {
                cancelAnimationFrame(animationIntervalRef.current);
            }
            toast.error('Error al realizar la rifa: ' + (error.message || 'Error desconocido'));
            return;
        }
    };

    const handleCloseModal = () => {
        if (animationIntervalRef.current) {
            cancelAnimationFrame(animationIntervalRef.current);
        }
        
        // Cerrar el modal primero
        onClose();
        
        // Si hay un ganador, notificar al componente padre para recargar
        if (winner && onWinnerSelected) {
            // Usar setTimeout para asegurar que el modal se cierre antes de recargar
            setTimeout(() => {
                onWinnerSelected(winner);
            }, 100);
        }
    };

    // La label es la compania del empleado
    const getCurrentDisplayEmployeeNumberLabel = () => {
        if (winner) {
            return winner.compania;
        }
        if (attendees.length > 0 && currentNameIndex < attendees.length) {
            return attendees[currentNameIndex]?.compania || '';
        }
        return '';
    };

    const getCurrentDisplayEmployeeNumber = () => {
        if (winner) {
            return winner.employee_number;
        }
        if (attendees.length > 0 && currentNameIndex < attendees.length) {
            return attendees[currentNameIndex]?.employee_number || '';
        }
        return '';
    };

    if (!selectedPrizeCard) return null;

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && handleCloseModal()}>
            <DialogContent className="raffle-modal" hideClose>
                {/* Área blanca central para el contenido */}
                <div className="raffle-modal-content"></div>
                
                <div className="raffle-modal-inner">
                    <button
                        onClick={handleCloseModal}
                        className="raffle-modal-close"
                    >
                        <span className="sr-only">Cerrar</span>
                        ✕
                    </button>

                    <div className="raffle-title">
                        <img 
                            src="/images/logo-gm.png" 
                            alt="GrupoMéxico" 
                            className={isLoading ? "raffle-logo-large" : "raffle-logo"} 
                        />
                    </div>
                    <p className={isLoading ? "raffle-subtitle-large" : "raffle-subtitle"}>
                        Sorteo de Fin de Año
                    </p>

                    {/* Estado durante animación */}
                    {isAnimating && !winner && (
                        <>
                            <div className="raffle-employee-number-box raffle-fade-in">
                                <p className="raffle-employee-number-animated">
                                    {getCurrentDisplayEmployeeNumberLabel()}
                                </p>
                                <p className="raffle-employee-number-animated">
                                    {getCurrentDisplayEmployeeNumber() || '---'}
                                </p>
                            </div>
                            
                            <div className="raffle-prize-box raffle-fade-in-delay">
                                <p className="raffle-prize-text">
                                    {selectedPrizeCard?.prizeName || 'PREMIO'}
                                </p>
                            </div>
                        </>
                    )}

                    {/* Estado ganador */}
                    {winner && (
                        <>                            
                            <div className="raffle-employee-number-box raffle-fade-in">
                                <p className="raffle-employee-number-label">
                                    {winner.compania}
                                </p>
                                <p className="raffle-employee-number-large">
                                    {winner.employee_number}
                                </p>
                            </div>
                            
                            <div className="raffle-prize-box raffle-fade-in-delay">
                                <p className="raffle-prize-text">
                                    {selectedPrizeCard?.prizeName?.toUpperCase() || 'PREMIO'}
                                </p>
                            </div>
                            
                            <div className="mb-6 raffle-fade-in-delay-2">
                                <p className="raffle-winner-text">
                                    ¡Ganador!
                                </p>
                                <p className="raffle-winner-name">
                                    {winner.name}
                                </p>
                            </div>
                        </>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}

