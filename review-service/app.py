from flask import Flask, jsonify, request
from pymongo import MongoClient
import os
from urllib.parse import quote_plus
import requests
from bson import ObjectId

app = Flask(__name__)

username = os.getenv('MONGO_USER', 'root')
password = os.getenv('MONGO_PASSWORD', 'Dev@root23')

encoding_username = quote_plus(username)
encoding_password = quote_plus(password)

mongo_host = os.getenv("MONGO_HOST", "localhost" if os.getenv("HOSTNAME") is None else "review-db") 
mongo_port = os.getenv("MONGO_PORT", 27017)
mongo_db = os.getenv("MONGO_DB", "reviewdb")
mongo_auth_source = os.getenv("MONGO_AUTH_SOURCE", "admin")

MONGO_URL = f"mongodb://{encoding_username}:{encoding_password}@{mongo_host}:{mongo_port}/{mongo_db}?authSource={mongo_auth_source}"

client = MongoClient(MONGO_URL)
db = client["review-db"]
reviews_collection = db['reviews']

product_service_host = "localhost" if os.getenv("HOSTNAME") is None else "product-service"
PRODUCT_SERVICE_URL  = f"http://{product_service_host}:3000/products/"


def get_product_data(product_id):
    try:
        response = requests.get(f'{PRODUCT_SERVICE_URL}{product_id}')
        if response.status_code == 200:
            return response.json().get("data")
        else:
            return None
    except requests.exceptions.RequestException as e:
        print(f"Error fetching product data: {e}")
        return None

def format_review_id(review):
    review["_id"] = str(review["_id"])
    return review

@app.route('/reviews', methods=['GET'])
def get_reviews():
    try:
        reviews = [format_review_id(review) for review in reviews_collection.find()]
        result = []
        for review in reviews:
            product_data = get_product_data(review["product_id"])
            if product_data:
                result.append({
                    "id": review["_id"],
                    "product_id": review["product_id"],
                    "review": review["review"],
                    "product": product_data
                })
        return jsonify({
            "message": "All reviews fetched successfully",
            "data": result
        }), 200
    except Exception as e:
        return jsonify({"error": str(e), "message": "Error fetching reviews"}), 500

@app.route("/products/<int:product_id>/reviews", methods=["GET"])
def get_reviews_by_product(product_id):
    try:
        product_data = get_product_data(product_id)
        if not product_data:
            return jsonify({"message": "Product not found"}), 404

        reviews = [format_review_id(review) for review in reviews_collection.find({"product_id": product_id})]
        response = {"product_id": product_id, "product": product_data, "reviews": reviews}

        return jsonify({"message": "Reviews fetched successfully", "data": response}), 200
    except Exception as e:
        return jsonify({"error": str(e), "message": "Error fetching reviews by product"}), 500

@app.route('/reviews', methods=['POST'])
def create_review():
    data = request.json
    product_id = data.get("product_id")
    ratings = data.get("ratings")
    comment = data.get("comment")

    if not product_id or not ratings or not comment:
        return jsonify({"message": "Missing product_id, ratings, or comment"}), 400

    product_data = get_product_data(product_id)
    if not product_data:
        return jsonify({"message": "Product not found"}), 404

    review = {
        "product_id": product_id,
        "review": {"ratings": ratings, "comment": comment}
    }

    result = reviews_collection.insert_one(review)
    return jsonify({"message": "Review created successfully", "id": str(result.inserted_id)}), 201

@app.route("/reviews/<string:review_id>", methods=["DELETE"])
def delete_review(review_id):
    result = reviews_collection.delete_one({"_id": ObjectId(review_id)})
    if result.deleted_count == 0:
        return jsonify({"message": "Review not found"}), 404
    return jsonify({"message": "Review deleted"}), 200


# Jalankan Flask di paling bawah
if __name__ == '__main__':
    app.run(host="0.0.0.0", port=3003, debug=True)