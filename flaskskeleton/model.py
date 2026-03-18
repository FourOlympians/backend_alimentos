import os

from flask_security import RoleMixin, UserMixin
from flask_sqlalchemy import SQLAlchemy
from flask_sqlalchemy.model import DefaultMeta

db = SQLAlchemy()

# This is a work around for mypy until SQLAlchemy supports type stubs.
BaseModel: DefaultMeta = db.Model


roles_users = db.Table(
    "roles_users",
    db.Column("user_id", db.Integer(), db.ForeignKey("user.id")),
    db.Column("role_id", db.Integer(), db.ForeignKey("role.id")),
)


class Role(BaseModel, RoleMixin):
    id = db.Column(db.Integer(), primary_key=True)
    name = db.Column(db.String(80), unique=True)
    description = db.Column(db.String(255))


class User(BaseModel, UserMixin):
    __tablename__ = "user"
    id = db.Column(db.Integer, primary_key=True)
    email = db.Column(db.String(64), unique=True)
    password = db.Column(db.String(255))
    fs_uniquifier = db.Column(db.String(255), unique=True, nullable=False)
    active = db.Column(db.Boolean())
    confirmed_at = db.Column(db.DateTime())
    roles = db.relationship(
        "Role", secondary=roles_users, backref=db.backref("users", lazy="dynamic")
    )
    tokens = db.relationship("OAuth2Token", back_populates="user")


class OAuth2Token(BaseModel):
    __tablename__ = "oauth2token"
    user_id = db.Column(db.Integer, db.ForeignKey("user.id"), primary_key=True)
    name = db.Column(db.String(20), primary_key=True)
    token_type = db.Column(db.String(20))
    access_token = db.Column(db.String(48), nullable=False)
    refresh_token = db.Column(db.String(48))
    expires_at = db.Column(db.Integer, default=0)
    #: ORM link to user table.
    user = db.relationship("User", back_populates="tokens")

    def __init__(
        self,
        user_id,
        name,
        token_type=None,
        access_token=None,
        refresh_token=None,
        expires_at=None,
    ):
        self.user_id = user_id
        self.name = name
        self.token_type = token_type
        self.access_token = access_token
        self.refresh_token = refresh_token
        self.expires_at = expires_at

    def from_token(self, token):
        self.token_type = token["token_type"]
        self.access_token = token["access_token"]
        self.refresh_token = token["refresh_token"]
        self.expires_at = token["expires_at"]

    def to_token(self):
        return dict(
            access_token=self.access_token,
            token_type=self.token_type,
            refresh_token=self.refresh_token,
            expires_at=self.expires_at,
        )


class GrupoAlimento(BaseModel):
    __tablename__ = "grupos_alimentos"

    id = db.Column(db.Integer, primary_key=True)
    nombre = db.Column(db.String(100), nullable=False, unique=True)

    alimentos = db.relationship("Alimento", back_populates="grupo", lazy="dynamic")

    def to_dict(self):
        return {
            "id": self.id,
            "nombre": self.nombre,
        }


class Alimento(BaseModel):
    __tablename__ = "alimentos"

    id = db.Column(db.Integer, primary_key=True)
    grupo_id = db.Column(db.Integer, db.ForeignKey("grupos_alimentos.id"), nullable=False)
    nombre = db.Column(db.String(255), nullable=False)

    # Medida casera
    cantidad_sugerida = db.Column(db.String(30))
    unidad = db.Column(db.String(50))

    # Peso
    peso_bruto_g = db.Column(db.Numeric(8, 2))
    peso_neto_g = db.Column(db.Numeric(8, 2))

    # Macronutrimentos
    energia_kcal = db.Column(db.Numeric(8, 2))
    proteina_g = db.Column(db.Numeric(8, 2))
    lipidos_g = db.Column(db.Numeric(8, 2))
    hidratos_carbono_g = db.Column(db.Numeric(8, 2))

    # Ácidos grasos
    ag_saturados_g = db.Column(db.Numeric(8, 2))
    ag_monoinsaturados_g = db.Column(db.Numeric(8, 2))
    ag_poliinsaturados_g = db.Column(db.Numeric(8, 2))

    # Micronutrimentos
    colesterol_mg = db.Column(db.Numeric(8, 2))
    azucar_g = db.Column(db.Numeric(8, 2))
    fibra_g = db.Column(db.Numeric(8, 2))
    vitamina_a_mg_re = db.Column(db.Numeric(8, 2))
    acido_ascorbico_mg = db.Column(db.Numeric(8, 2))
    acido_folico_mg = db.Column(db.Numeric(8, 2))
    calcio_mg = db.Column(db.Numeric(8, 2))
    hierro_mg = db.Column(db.Numeric(8, 2))
    potasio_mg = db.Column(db.Numeric(8, 2))
    sodio_mg = db.Column(db.Numeric(8, 2))
    fosforo_mg = db.Column(db.Numeric(8, 2))
    etanol_g = db.Column(db.Numeric(8, 2))

    # Índice glucémico
    indice_glucemico = db.Column(db.Numeric(5, 1))
    carga_glucemica = db.Column(db.Numeric(5, 1))

    #: ORM link to grupo table.
    grupo = db.relationship("GrupoAlimento", back_populates="alimentos")

    def to_dict(self):
        return {
            "id": self.id,
            "grupo_id": self.grupo_id,
            "nombre": self.nombre,
            "cantidad_sugerida": self.cantidad_sugerida,
            "unidad": self.unidad,
            "peso_bruto_g": float(self.peso_bruto_g) if self.peso_bruto_g is not None else None,
            "peso_neto_g": float(self.peso_neto_g) if self.peso_neto_g is not None else None,
            "energia_kcal": float(self.energia_kcal) if self.energia_kcal is not None else None,
            "proteina_g": float(self.proteina_g) if self.proteina_g is not None else None,
            "lipidos_g": float(self.lipidos_g) if self.lipidos_g is not None else None,
            "hidratos_carbono_g": float(self.hidratos_carbono_g) if self.hidratos_carbono_g is not None else None,
            "ag_saturados_g": float(self.ag_saturados_g) if self.ag_saturados_g is not None else None,
            "ag_monoinsaturados_g": float(self.ag_monoinsaturados_g) if self.ag_monoinsaturados_g is not None else None,
            "ag_poliinsaturados_g": float(self.ag_poliinsaturados_g) if self.ag_poliinsaturados_g is not None else None,
            "colesterol_mg": float(self.colesterol_mg) if self.colesterol_mg is not None else None,
            "azucar_g": float(self.azucar_g) if self.azucar_g is not None else None,
            "fibra_g": float(self.fibra_g) if self.fibra_g is not None else None,
            "vitamina_a_mg_re": float(self.vitamina_a_mg_re) if self.vitamina_a_mg_re is not None else None,
            "acido_ascorbico_mg": float(self.acido_ascorbico_mg) if self.acido_ascorbico_mg is not None else None,
            "acido_folico_mg": float(self.acido_folico_mg) if self.acido_folico_mg is not None else None,
            "calcio_mg": float(self.calcio_mg) if self.calcio_mg is not None else None,
            "hierro_mg": float(self.hierro_mg) if self.hierro_mg is not None else None,
            "potasio_mg": float(self.potasio_mg) if self.potasio_mg is not None else None,
            "sodio_mg": float(self.sodio_mg) if self.sodio_mg is not None else None,
            "fosforo_mg": float(self.fosforo_mg) if self.fosforo_mg is not None else None,
            "etanol_g": float(self.etanol_g) if self.etanol_g is not None else None,
            "indice_glucemico": float(self.indice_glucemico) if self.indice_glucemico is not None else None,
            "carga_glucemica": float(self.carga_glucemica) if self.carga_glucemica is not None else None,
        }


def make_conn_str():
    """Make an local database file on disk."""
    return "sqlite:///{cwd}/database.db".format(cwd=os.path.abspath(os.getcwd()))