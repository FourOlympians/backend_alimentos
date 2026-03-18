# flaskskeleton/alimentos_api.py
# -----------------------------------------------------------
# Blueprint: API REST de Alimentos y Grupos
# Base URL: /api
# -----------------------------------------------------------
# Endpoints:
#   GET /api/grupos                          → lista todos los grupos
#   GET /api/alimentos                       → lista paginada (filtros opcionales)
#   GET /api/alimentos/<id>                  → detalle de un alimento
#   GET /api/alimentos/buscar?q=...          → búsqueda por nombre
# -----------------------------------------------------------

from flask import Blueprint, jsonify, request

from flaskskeleton.model import Alimento, GrupoAlimento

api_bp = Blueprint("api", __name__, url_prefix="/api")

PAGE_SIZE = 30


def _ok(data, meta=None):
    body = {"ok": True, "data": data}
    if meta:
        body["meta"] = meta
    return jsonify(body), 200


def _err(msg, code=400):
    return jsonify({"ok": False, "error": msg}), code


# ── GET /api/grupos ───────────────────────────────────────────
@api_bp.route("/grupos", methods=["GET"])
def get_grupos():
    """
    Devuelve todos los grupos de alimentos.

    Response JSON:
    {
        "ok": true,
        "data": [
            { "id": 1, "nombre": "Verduras" },
            ...
        ]
    }
    """
    grupos = GrupoAlimento.query.order_by(GrupoAlimento.id).all()
    return _ok([g.to_dict() for g in grupos])


# ── GET /api/alimentos ────────────────────────────────────────
@api_bp.route("/alimentos", methods=["GET"])
def get_alimentos():
    """
    Lista alimentos con filtros opcionales y paginación.

    Query params:
        grupo_id  (int)    → filtra por grupo
        page      (int)    → número de página, empieza en 1 (default: 1)

    Response JSON:
    {
        "ok": true,
        "data": [ { ...alimento... }, ... ],
        "meta": { "page": 1, "page_size": 30, "total": 2867 }
    }
    """
    grupo_id = request.args.get("grupo_id", type=int)
    page = max(1, request.args.get("page", 1, type=int))

    query = Alimento.query

    if grupo_id:
        query = query.filter_by(grupo_id=grupo_id)

    paginado = query.order_by(Alimento.nombre).paginate(page=page, per_page=PAGE_SIZE, error_out=False)

    return _ok(
        [a.to_dict() for a in paginado.items],
        meta={
            "page": page,
            "page_size": PAGE_SIZE,
            "total": paginado.total,
        },
    )


# ── GET /api/alimentos/buscar ─────────────────────────────────
@api_bp.route("/alimentos/buscar", methods=["GET"])
def buscar_alimentos():
    """
    Búsqueda de alimentos por nombre con filtro de grupo opcional.

    Query params:
        q         (str)    → término de búsqueda (mínimo 2 caracteres)
        grupo_id  (int)    → filtra por grupo (opcional)
        page      (int)    → paginación (default: 1)

    Response JSON:
    {
        "ok": true,
        "data": [ { ...alimento... }, ... ],
        "meta": { "page": 1, "page_size": 30, "total": 12 }
    }
    """
    q = request.args.get("q", "").strip()
    grupo_id = request.args.get("grupo_id", type=int)
    page = max(1, request.args.get("page", 1, type=int))

    if len(q) < 2:
        return _err("El parámetro 'q' debe tener al menos 2 caracteres.")

    query = Alimento.query.filter(Alimento.nombre.ilike(f"%{q}%"))

    if grupo_id:
        query = query.filter_by(grupo_id=grupo_id)

    paginado = query.order_by(Alimento.nombre).paginate(page=page, per_page=PAGE_SIZE, error_out=False)

    return _ok(
        [a.to_dict() for a in paginado.items],
        meta={
            "page": page,
            "page_size": PAGE_SIZE,
            "total": paginado.total,
        },
    )


# ── GET /api/alimentos/<id> ───────────────────────────────────
@api_bp.route("/alimentos/<int:alimento_id>", methods=["GET"])
def get_alimento(alimento_id):
    """
    Devuelve el detalle completo de un alimento por su ID.

    Response JSON:
    {
        "ok": true,
        "data": { ...todos los campos del alimento... }
    }
    """
    alimento = Alimento.query.get(alimento_id)

    if not alimento:
        return _err("Alimento no encontrado.", 404)

    return _ok(alimento.to_dict())


# ── Registro del blueprint en __init__.py ─────────────────────
# En tu flaskskeleton/__init__.py agrega estas dos líneas:
#
#   from flaskskeleton.alimentos_api import api_bp
#   app.register_blueprint(api_bp)