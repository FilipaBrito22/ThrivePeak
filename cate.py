import traceback

try:
    # Your existing Python code
    import pandas as pd
    import matplotlib.pyplot as plt
    from sklearn.feature_extraction.text import TfidfVectorizer
    from sklearn.cluster import KMeans

    # Load data from the CSV generated by PHP
    df = pd.read_csv("criteria_input.csv")

    # Use TF-IDF to categorize criteria into clusters
    tfidf = TfidfVectorizer()
    tfidf_matrix = tfidf.fit_transform(df["criterion"])

    # KMeans clustering (2 clusters: Communication and Technical)
    kmeans = KMeans(n_clusters=2, random_state=42)
    df["category"] = kmeans.fit_predict(tfidf_matrix)

    # Map cluster IDs to meaningful categories
    categories = {0: "Communication", 1: "Technical"}
    df["category"] = df["category"].map(categories)

    # Calculate intervention priority (10 - score)
    df["intervention_priority"] = df["score"].apply(lambda x: max(0, 10 - x))

    # Save categorized data back to a CSV for PHP
    df.to_csv("categorized_criteria.csv", index=False)

    # Plot the matrix
    plt.figure(figsize=(10, 7))
    for category, group in df.groupby("category"):
        plt.scatter(
            group["score"],
            group["intervention_priority"],
            label=category,
            s=100, alpha=0.7
        )

    plt.title("Skill Performance Matrix")
    plt.xlabel("Mean Performance Score")
    plt.ylabel("Mean Intervention Priority")
    plt.legend(title="Category")
    plt.grid(True)
    plt.show()

except Exception as e:
    with open("python_error_log.txt", "w") as f:
        f.write(traceback.format_exc())
