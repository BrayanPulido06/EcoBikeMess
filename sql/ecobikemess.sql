CREATE DATABASE IF NOT EXISTS ecobikemess;
use ecobikemess;

CREATE TABLE IF NOT EXISTS tp_registro (id INT AUTO_INCREMENT NOT NULL,
                                        correo VARCHAR(100) NOT NULL,
                                        password VARCHAR(100) NOT NULL,
                                        telefono VARCHAR(15) NOT NULL,
                                        nombre VARCHAR(50) NOT NULL,
                                        estado INT(1) NOT NULL,

                                        PRIMARY KEY (id)
);



CREATE TABLE IF NOT EXISTS tp_servicio (id INT AUTO_INCREMENT NOT NULL,
                                        correo 	VARCHAR (100)NOT NULL,
                                        nombres VARCHAR(100) NOT NULL,
                                        telefono VARCHAR(15) NOT NULL,
                                        direccion VARCHAR(50) NOT NULL,
                                        cobro VARCHAR(30) NOT NULL,
                                        observacion varchar(100) NOT NULL,

                                        PRIMARY KEY (id)
)ENGINE=INNODB;



CREATE TABLE IF NOT EXISTS tp_comprobante (id INT AUTO_INCREMENT NOT NULL,
                                        observacion VARCHAR(100),
                                        foto VARCHAR(500) NOT NULL,
                                        foto2 VARCHAR(500) NOT NULL,
                                        fecha DATE NOT NULL,
                                        hora TIME NOT NULL,

                                        PRIMARY KEY (id)

)ENGINE=INNODB;


/*inserts*/
INSERT INTO tp_registro VALUES 
(NULL, 'brayan06.pulido@gmail.com', '123456789', '3172509298', 'Brayan', '1'), 
(NULL, 'brayan@gmail.com', '987654321', '3187844160', 'Felipe', '0');