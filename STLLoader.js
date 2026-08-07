/**
 * STL Loader for Three.js r128+
 * Compatible with modern Three.js versions
 */

class STLLoader extends THREE.Loader {
    constructor(manager) {
        super(manager);
    }

    load(url, onLoad, onProgress, onError) {
        const scope = this;
        const loader = new THREE.FileLoader(scope.manager);
        loader.setPath(scope.path);
        loader.setResponseType('arraybuffer');
        loader.setRequestHeader(scope.requestHeader);
        loader.setWithCredentials(scope.withCredentials);
        
        loader.load(url, function(text) {
            try {
                onLoad(scope.parse(text));
            } catch (e) {
                if (onError) {
                    onError(e);
                } else {
                    console.error(e);
                }
                scope.manager.itemError(url);
            }
        }, onProgress, onError);
    }

    parse(data) {
        function isBinary(data) {
            const reader = new DataView(data);
            const face_size = (32 / 8 * 3) + ((32 / 8 * 3) * 3) + (16 / 8);
            const n_faces = reader.getUint32(80, true);
            const expect = 80 + (32 / 8) + (n_faces * face_size);
            
            if (expect === reader.byteLength) {
                return true;
            }
            
            const fileLength = reader.byteLength;
            if (80 + 32 / 8 + n_faces * face_size <= fileLength) {
                return true;
            }
            
            return false;
        }

        function parseBinary(data) {
            const reader = new DataView(data);
            const faces = reader.getUint32(80, true);
            
            let r, g, b, hasColors = false, colors;
            let defaultR, defaultG, defaultB, alpha;
            
            for (let index = 0; index < 80 - 10; index++) {
                if ((reader.getUint32(index, false) == 0x434F4C4F /*COLO*/) && 
                    (reader.getUint32(index + 4, false) == 0x523D /*R=*/)) {
                    hasColors = true;
                    colors = new Float32Array(faces * 3 * 3);
                    defaultR = reader.getUint8(index + 6) / 255;
                    defaultG = reader.getUint8(index + 7) / 255;
                    defaultB = reader.getUint8(index + 8) / 255;
                    alpha = reader.getUint8(index + 9) / 255;
                }
            }
            
            const dataOffset = 84;
            const faceLength = 12 * 4 + 2;
            
            const geometry = new THREE.BufferGeometry();
            const vertices = new Float32Array(faces * 3 * 3);
            const normals = new Float32Array(faces * 3 * 3);
            
            for (let face = 0; face < faces; face++) {
                const start = dataOffset + face * faceLength;
                const normalX = reader.getFloat32(start, true);
                const normalY = reader.getFloat32(start + 4, true);
                const normalZ = reader.getFloat32(start + 8, true);
                
                if (hasColors) {
                    const packedColor = reader.getUint16(start + 48, true);
                    
                    if ((packedColor & 0x8000) === 0) {
                        r = (packedColor & 0x1F) / 31;
                        g = ((packedColor >> 5) & 0x1F) / 31;
                        b = ((packedColor >> 10) & 0x1F) / 31;
                    } else {
                        r = defaultR;
                        g = defaultG;
                        b = defaultB;
                    }
                }
                
                for (let i = 1; i <= 3; i++) {
                    const vertexstart = start + i * 12;
                    const componentIdx = (face * 3 * 3) + ((i - 1) * 3);
                    
                    vertices[componentIdx] = reader.getFloat32(vertexstart, true);
                    vertices[componentIdx + 1] = reader.getFloat32(vertexstart + 4, true);
                    vertices[componentIdx + 2] = reader.getFloat32(vertexstart + 8, true);
                    
                    normals[componentIdx] = normalX;
                    normals[componentIdx + 1] = normalY;
                    normals[componentIdx + 2] = normalZ;
                    
                    if (hasColors) {
                        colors[componentIdx] = r;
                        colors[componentIdx + 1] = g;
                        colors[componentIdx + 2] = b;
                    }
                }
            }
            
            geometry.setAttribute('position', new THREE.BufferAttribute(vertices, 3));
            geometry.setAttribute('normal', new THREE.BufferAttribute(normals, 3));
            
            if (hasColors) {
                geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));
                geometry.hasColors = true;
                geometry.alpha = alpha;
            }
            
            return geometry;
        }

        function parseASCII(data) {
            const geometry = new THREE.BufferGeometry();
            const patternFace = /facet([\s\S]*?)endfacet/g;
            const patternNormal = /normal\s+([\-+]?[0-9]+\.?[0-9]*([eE][\-+]?[0-9]+)?)\s+([\-+]?[0-9]+\.?[0-9]*([eE][\-+]?[0-9]+)?)\s+([\-+]?[0-9]+\.?[0-9]*([eE][\-+]?[0-9]+)?)/g;
            const patternVertex = /vertex\s+([\-+]?[0-9]+\.?[0-9]*([eE][\-+]?[0-9]+)?)\s+([\-+]?[0-9]+\.?[0-9]*([eE][\-+]?[0-9]+)?)\s+([\-+]?[0-9]+\.?[0-9]*([eE][\-+]?[0-9]+)?)/g;
            
            const vertices = [];
            const normals = [];
            
            let result;
            
            while ((result = patternFace.exec(data)) !== null) {
                let vertexCountPerFace = 0;
                let normalCountPerFace = 0;
                
                const text = result[0];
                
                while ((result = patternNormal.exec(text)) !== null) {
                    normalCountPerFace++;
                }
                
                while ((result = patternVertex.exec(text)) !== null) {
                    vertices.push(parseFloat(result[1]), parseFloat(result[3]), parseFloat(result[5]));
                    vertexCountPerFace++;
                }
                
                patternNormal.lastIndex = 0;
                
                while ((result = patternNormal.exec(text)) !== null) {
                    for (let i = 0; i < vertexCountPerFace; i++) {
                        normals.push(parseFloat(result[1]), parseFloat(result[3]), parseFloat(result[5]));
                    }
                }
            }
            
            geometry.setAttribute('position', new THREE.Float32BufferAttribute(vertices, 3));
            geometry.setAttribute('normal', new THREE.Float32BufferAttribute(normals, 3));
            
            return geometry;
        }

        function ensureString(buffer) {
            if (typeof buffer !== 'string') {
                return THREE.LoaderUtils.decodeText(new Uint8Array(buffer));
            }
            return buffer;
        }

        function ensureBinary(buffer) {
            if (typeof buffer === 'string') {
                const array_buffer = new Uint8Array(buffer.length);
                for (let i = 0; i < buffer.length; i++) {
                    array_buffer[i] = buffer.charCodeAt(i) & 0xff;
                }
                return array_buffer.buffer || array_buffer;
            } else {
                return buffer;
            }
        }

        const binData = ensureBinary(data);
        return isBinary(binData) ? parseBinary(binData) : parseASCII(ensureString(data));
    }
}

// Attach to THREE object
THREE.STLLoader = STLLoader;
