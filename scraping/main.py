import os
import csv
import logging
from selenium import webdriver
from selenium.webdriver.chrome.service import Service  # Importa Service
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time
from utils import clean_text, split_format_and_pages
from config import BASE_URL, WAIT_TIME 

# Crear carpetas si no existen
os.makedirs("logs", exist_ok=True)  # Crea la carpeta de logs si no existe
os.makedirs("data", exist_ok=True)  # Crea la carpeta de datos si no existe

# Configuración de logging
# Solo mensajes críticos en el archivo de logs
logging.basicConfig(
    filename="logs/scraping.log",  # Archivo de log para almacenar mensajes
    level=logging.CRITICAL,  # Solo se registrarán mensajes de nivel CRITICAL
    format="%(asctime)s - %(levelname)s - %(message)s",  # Formato de los logs
)

# Configuración de logging para la consola: solo errores graves
console = logging.StreamHandler()  # Para mostrar logs en la consola
console.setLevel(logging.CRITICAL)  # Solo se mostrarán mensajes CRITICAL
console.setFormatter(logging.Formatter('%(message)s'))  # Formato del log en consola
logging.getLogger().addHandler(console)  # Se añade el handler a la configuración global

# Configuración de Selenium
options = Options()  # Opciones del navegador para evitar fallos de ejecución en entornos sin UI
options.add_argument("--no-sandbox")  # Opción de seguridad para ambientes restringidos
options.add_argument("--disable-dev-shm-usage")  # Evitar uso de memoria compartida en sistemas limitados

# Establecer la ruta completa de chromedriver
chromedriver_path = "/usr/bin/chromedriver"

# Crear una instancia de Service para pasar la ruta de chromedriver
service = Service(executable_path=chromedriver_path)

# Inicializar el navegador Chrome utilizando el servicio
driver = webdriver.Chrome(service=service, options=options)
wait = WebDriverWait(driver, WAIT_TIME)  # Tiempo de espera para elementos del navegador

# Archivo CSV para almacenar los resultados
csv_file_path = "data/books.csv"  # Ruta del archivo CSV donde se guardarán los datos
fieldnames = [
    "Título",  # Título del libro
    "Autor",  # Autor del libro
    "Descripción",  # Descripción del libro
    "Formato",  # Formato del libro (e.g., tapa dura, tapa blanda)
    "Número de páginas",  # Cantidad de páginas
    "Fecha de publicación",  # Fecha de publicación del libro
    "Calificación promedio",  # Calificación promedio del libro
    "Número de calificaciones",  # Cantidad de calificaciones recibidas
    "Número de reseñas",  # Cantidad de reseñas del libro
    "URL de la imagen de portada",  # Enlace a la imagen de portada
    "URL del libro",  # Enlace directo a la página del libro
    "Categoría",  # Categoría o género del libro
]

# Crear o inicializar el archivo CSV si no existe
if not os.path.exists(csv_file_path):
    with open(csv_file_path, mode="w", newline="", encoding="utf-8") as csvfile:
        writer = csv.DictWriter(csvfile, fieldnames=fieldnames)  # Inicializa el archivo CSV
        writer.writeheader()  # Escribe los encabezados de las columnas

# Función para obtener calificaciones
def get_ratings(driver):
    """Extrae la calificación promedio, número de calificaciones y número de reseñas."""
    try:
        rating = driver.find_element(By.CSS_SELECTOR, "div.RatingStatistics__rating").text
    except Exception:
        rating = "Calificación no encontrada"  # En caso de error

    try:
        ratings_count = driver.find_element(By.CSS_SELECTOR, "span[data-testid='ratingsCount']").text
    except Exception:
        ratings_count = "Número de calificaciones no encontrado"  # En caso de error

    try:
        reviews_count = driver.find_element(By.CSS_SELECTOR, "span[data-testid='reviewsCount']").text
    except Exception:
        reviews_count = "Número de reseñas no encontrado"  # En caso de error

    return rating, ratings_count, reviews_count  # Devuelve los datos extraídos

# Función principal
try:
    driver.get(BASE_URL)  # Carga la URL base
    logging.info("Página principal cargada.")  # Mensaje de carga de la página

    # Obtener enlaces de categorías
    category_links = driver.find_elements(By.CSS_SELECTOR, "div.u-defaultType a.gr-hyperlink")  # Encuentra los enlaces a categorías
    categories = {clean_text(link.text): link.get_attribute("href") for link in category_links}  # Crear un diccionario con categorías
    logging.info(f"Se encontraron {len(categories)} categorías.")  # Informa la cantidad de categorías encontradas

    # Recorremos todas las categorías hasta encontrar "More genres"
    for category_name, category_url in categories.items():
        # Verificar si la categoría es "More genres"
        if "More genres" in category_name:
            logging.critical("Se encontró la sección 'More genres'. Parando el scraping de categorías.")
            break  # Detiene el scraping si encuentra esta categoría

        # Manejar errores si no se puede procesar la categoría
        try:
            driver.get(category_url)  # Carga la página de la categoría

            # Validar libros visibles
            wait.until(EC.presence_of_all_elements_located((By.CSS_SELECTOR, "a.bookTitle")))  # Espera hasta que los libros sean visibles
            book_links = [link.get_attribute("href") for link in driver.find_elements(By.CSS_SELECTOR, "a.bookTitle")]  # Extrae los enlaces de los libros

            if not book_links:
                logging.warning(f"No se encontraron libros en la categoría: {category_name}")  # Si no hay libros, se muestra un aviso
            else:
                # Recorrer todos los libros en la categoría
                for book_url in book_links:
                    try:
                        driver.get(book_url)  # Carga la página del libro
                        book_data = {"URL del libro": book_url, "Categoría": category_name}  # Diccionario para almacenar los datos del libro

                        # Extraer información del libro
                        book_data["Título"] = clean_text(
                            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1[data-testid='bookTitle']"))).text
                        )
                        book_data["Autor"] = clean_text(
                            driver.find_element(By.CSS_SELECTOR, "span.ContributorLink__name").text
                        )
                        book_data["Descripción"] = clean_text(
                            driver.find_element(By.CSS_SELECTOR, "div[data-testid='description'] span.Formatted").text
                        )
                        pages_format = driver.find_element(By.CSS_SELECTOR, "p[data-testid='pagesFormat']").text
                        book_data["Formato"], book_data["Número de páginas"] = split_format_and_pages(pages_format)
                        book_data["Fecha de publicación"] = clean_text(
                            driver.find_element(By.CSS_SELECTOR, "p[data-testid='publicationInfo']").text
                        )
                        book_data["Calificación promedio"], book_data["Número de calificaciones"], book_data["Número de reseñas"] = get_ratings(driver)
                        book_data["URL de la imagen de portada"] = driver.find_element(By.CSS_SELECTOR, "img.ResponsiveImage").get_attribute("src")

                        # Guardar en el archivo CSV
                        with open(csv_file_path, mode="a", newline="", encoding="utf-8") as csvfile:
                            writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
                            writer.writerow(book_data)  # Escribe los datos en el archivo CSV

                        logging.critical(f"Libro scrapeado: {book_data['Título']}")  # Solo información importante

                    except Exception as book_error:
                        logging.critical(f"Error al procesar el libro en {book_url}: {book_error}")
                        continue  # Continúa con el siguiente libro

        except Exception as category_error:
            logging.critical(f"Error al procesar la categoría {category_name}: {category_error}")
            continue  # Continúa con la siguiente categoría

except Exception as e:
    logging.critical(f"Error durante la ejecución principal: {e}")  # Captura errores globales
finally:
    driver.quit()  # Cierra el navegador
    logging.critical("Navegador cerrado.")  # Registro de cierre del navegador
