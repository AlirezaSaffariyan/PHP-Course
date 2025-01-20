import sys
import requests
from bs4 import BeautifulSoup
from urllib.parse import urlparse


def get_album_cover_art(artist, album):
    # Google Images search URL
    url = f"https://www.google.com/search?q={artist}+{album}+album+art&tbm=isch"

    # Send a GET request
    response = requests.get(url, headers={"User-Agent": "Mozilla/5.0"})

    # If the GET request is successful, the status code will be 200
    if response.status_code == 200:
        # Get the content of the response
        page_content = response.content

        # Create a BeautifulSoup object and specify the parser
        soup = BeautifulSoup(page_content, "html.parser")

        # Find all images on the page
        images = soup.find_all("img")

        # Initialize variables to store the image URL and source
        image_url = None

        # Iterate over the images
        for image in images:
            # Get the image URL
            img_url = image.get("src")

            # Check if the image URL is not empty
            if img_url:
                # Parse the image URL
                parsed_url = urlparse(img_url)

                # Check if the image URL is a relative URL
                if bool(parsed_url.netloc):
                    # Store the image URL and source
                    image_url = img_url
                    # Break the loop
                    break

        # If an image URL is found
        if image_url:
            return image_url
        else:
            return "No cover art found"
    else:
        return f"Failed to retrieve page content from {url}"


if __name__ == "__main__":
    artist_name = sys.argv[1]
    album_name = sys.argv[2]

    cover_art = get_album_cover_art(artist_name, album_name)

    if cover_art:
        print(cover_art)
    else:
        print("")
