"""
Scrapers package for HSK learning sources.
"""

from .xiehanzi_scraper import XieHanziScraper
from .mandarinbean_scraper import MandarinBeanScraper
from .hihsk_scraper import HiHSKScraper
from .migii_scraper import MigiiScraper

__all__ = [
    'XieHanziScraper',
    'MandarinBeanScraper',
    'HiHSKScraper',
    'MigiiScraper',
]
