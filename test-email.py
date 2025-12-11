#!/usr/bin/env python3
"""
Script de prueba de correo - Python con Office 365
Prueba la configuración SMTP con TLS y autenticación
"""

import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from datetime import datetime
import sys

# Configuración del servidor SMTP para Office 365
SMTP_HOST = 'smtp.office365.com'
SMTP_PORT = 587  # Puerto para STARTTLS
FROM_EMAIL = 'laviaquenosune@ferromex.mx'
FROM_NAME = 'Eventos'
TO_EMAIL = 'desarrollo@peltiermkt.com'
SMTP_USERNAME = 'laviaquenosune@ferromex.mx'
SMTP_PASSWORD = '1Fxe#Gmxt'

def test_connection():
    """Prueba la conexión al servidor SMTP con TLS"""
    print("═" * 60)
    print("  PRUEBA DE CONEXIÓN SMTP - Office 365 con TLS")
    print("═" * 60)
    print()
    
    try:
        print(f"Conectando a {SMTP_HOST}:{SMTP_PORT}...")
        server = smtplib.SMTP(SMTP_HOST, SMTP_PORT, timeout=10)
        server.set_debuglevel(1)  # Mostrar debug info
        
        print("\n✓ Conexión establecida")
        
        # Iniciar TLS (REQUERIDO para Office 365)
        print("\nIniciando STARTTLS...")
        server.starttls()
        print("✓ TLS establecido")
        
        print("\nEnviando EHLO después de TLS...")
        server.ehlo()
        
        # Autenticar (REQUERIDO para Office 365)
        print(f"\nAutenticando como {SMTP_USERNAME}...")
        server.login(SMTP_USERNAME, SMTP_PASSWORD)
        print("✓ Autenticación exitosa")
        
        return server
    except smtplib.SMTPAuthenticationError as e:
        print(f"\n✗ Error de autenticación: {e}")
        print("\nVerifica:")
        print("  1. Que las credenciales sean correctas")
        print("  2. Que 'Authenticated SMTP' esté habilitado en Office 365")
        print("  3. Si tienes MFA, usa una contraseña de aplicación")
        return None
    except Exception as e:
        print(f"\n✗ Error de conexión: {e}")
        return None

def send_test_email(server):
    """Envía un correo de prueba"""
    try:
        # Crear el mensaje
        msg = MIMEMultipart()
        msg['From'] = f'{FROM_NAME} <{FROM_EMAIL}>'
        msg['To'] = TO_EMAIL
        msg['Subject'] = f'Prueba de correo Python - {datetime.now().strftime("%Y-%m-%d %H:%M:%S")}'
        
        # Cuerpo del mensaje
        body = f"""Este es un correo de prueba enviado desde Python con TLS.

Detalles de la conexión:
- Servidor: {SMTP_HOST}:{SMTP_PORT}
- TLS: HABILITADO (STARTTLS)
- Autenticación: SÍ
- Usuario: {SMTP_USERNAME}
- Desde: {FROM_EMAIL}
- Fecha: {datetime.now().strftime("%Y-%m-%d %H:%M:%S")}

Este correo se envió con autenticación y encriptación TLS.
Configuración válida para Office 365.
"""
        
        msg.attach(MIMEText(body, 'plain', 'utf-8'))
        
        print("\n" + "═" * 60)
        print("  ENVIANDO CORREO")
        print("═" * 60)
        print(f"\nFrom: {FROM_EMAIL}")
        print(f"To: {TO_EMAIL}")
        print(f"Subject: {msg['Subject']}")
        print("\nEnviando...")
        
        # Enviar el correo
        server.send_message(msg)
        
        print("\n✓ ¡Correo enviado exitosamente!")
        print(f"  Verifica la bandeja de entrada de {TO_EMAIL}")
        
        return True
        
    except Exception as e:
        print(f"\n✗ Error al enviar el correo: {e}")
        return False

def main():
    """Función principal"""
    # Probar conexión
    server = test_connection()
    
    if server is None:
        print("\nNo se pudo establecer conexión con el servidor SMTP")
        sys.exit(1)
    
    # Enviar correo de prueba
    success = send_test_email(server)
    
    # Cerrar conexión
    print("\nCerrando conexión...")
    server.quit()
    
    print("\n" + "═" * 60)
    
    sys.exit(0 if success else 1)

if __name__ == '__main__':
    main()
