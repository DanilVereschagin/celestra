<?php
declare(strict_types=1);

require_once __DIR__ . '/src/VectorSearch.php';

use Celestra\VectorSearch;

$search = new VectorSearch();

$query = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$results = $search->search($query);
?>

<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Celestra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  </head>
  <body>
    <div class="container py-4">
      <div class="row mb-4">
        <div class="col-lg-10 mx-auto">
          <div class="card shadow-sm border border-success">
            <div class="card-body">

              <h1 class="h4 mb-3">Векторный поиск</h1>
              <form class="row gy-2 gx-2 align-items-center" method="get" action="?q=">
                <div class="col-12 col-md-9">
                  <input type="text" class="form-control form-control-lg border-success" name="q" value="<?= $query ?>" placeholder="Поиск...">
                </div>
                <div class="col-12 col-md-3 d-grid">
                  <button type="submit" class="btn btn-success btn-lg">Найти</button>
                </div>
              </form>

            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-10 mx-auto">

            <?php if (empty($results)): ?>
                <?php if ($query === ''): ?>     
                <?php else: ?>
              <div class="alert alert-warning" role="alert">
                По вашему запросу результаты не найдены.
              </div>
            <?php endif; ?>

            <?php else: ?>
              <div class="card shadow-sm border border-success">
                <div class="card-body">
                  <h2 class="h5 mb-3 text-success">Результаты поиска</h2>
                  <div class="g-5">

                    <?php foreach ($results as $item): ?>
                      <?php $pct = ($item['coincidence'] ?? 0.0) * 100.0; ?>
                      <div class="list-group-item py-3 border-bottom border-success">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                          <div class="fw-bold">Сходство</div>
                          <span class="badge text-bg-success"><?= number_format($pct, 2, '.') ?>%</span>
                        </div>
                        <div class="progress mb-2" role="progressbar" aria-valuenow="<?= (int)$pct ?>" aria-valuemin="0" aria-valuemax="100">
                          <div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div>
                        </div>
                        <div class="text-body">
                          <?= $item['text'] ?? '' ?>
                        </div>
                      </div>
                    <?php endforeach; ?>

                  </div>
                </div>
              </div>
            <?php endif; ?>

        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  </body>
</html>


